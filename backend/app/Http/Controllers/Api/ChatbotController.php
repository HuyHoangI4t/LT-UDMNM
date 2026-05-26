<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatLog;
use App\Models\FaqQuestion;
use App\Services\AiChatService;
use App\Services\KnowledgeSearchService;
use App\Services\QuestionAnalyzerService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class ChatbotController extends Controller
{
    public function __construct(
        protected AiChatService $aiChatService,
        protected QuestionAnalyzerService $questionAnalyzerService,
        protected KnowledgeSearchService $knowledgeSearchService
    ) {
    }

    #[OA\Post(
        path: '/api/chat',
        summary: 'Gửi câu hỏi cho Chatbot AI',
        tags: ['Chatbot'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['message'],
                properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Nganh Cong nghe thong tin lay bao nhieu diem?'),
                    new OA\Property(property: 'platform', type: 'string', example: 'web'),
                    new OA\Property(property: 'history', type: 'array', items: new OA\Items(type: 'object')),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Chatbot tra loi thanh cong'),
            new OA\Response(response: 422, description: 'Du lieu khong hop le'),
        ]
    )]
    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
            'platform' => 'nullable|string',
            'history' => 'nullable|array',
            'history.*.role' => 'nullable|string|in:user,ai,assistant',
            'history.*.text' => 'nullable|string|max:2000',
        ]);

        $startTime = microtime(true);
        $userMessage = trim($request->input('message'));
        $platform = $request->input('platform', 'web');
        $history = $request->input('history', []);
        $sessionId = $request->header('X-Session-ID', Str::uuid()->toString());

        try {
            $analysis = $this->questionAnalyzerService->analyze($userMessage);
            $agentSteps = [
                [
                    'step' => 'intent_detection',
                    'status' => 'completed',
                    'detail' => 'Phân tích ý định và trích xuất thực thể.',
                ],
            ];

            $knowledge = $this->knowledgeSearchService->searchSmart($userMessage, $analysis, 6);
            $agentSteps[] = [
                'step' => 'rag_retrieval',
                'status' => 'completed',
                'detail' => 'Truy xuất MySQL/knowledge base và semantic search nếu được bật.',
            ];

            $cacheKey = $this->buildChatCacheKey($userMessage, $knowledge, $analysis, $history);
            $cacheHit = Cache::has($cacheKey);
            $aiError = null;

            try {
                $botReply = Cache::remember(
                    $cacheKey,
                    (int) env('CHAT_CACHE_TTL_SECONDS', 3600),
                    fn () => $this->aiChatService->getAnswer($userMessage, $knowledge, $analysis, $history)
                );
            } catch (\Throwable $e) {
                $aiError = $e->getMessage();
                $botReply = $this->buildFallbackReply($knowledge);
            }

            $botReply = $this->normalizeSourceLines($botReply);

            $agentSteps[] = [
                'step' => 'llm_generation',
                'status' => $aiError ? 'degraded' : 'completed',
                'detail' => $aiError
                    ? 'AI đang tạm thời không khả dụng, dùng câu trả lời dự phòng từ dữ liệu đã truy xuất.'
                    : ($cacheHit
                    ? 'Dùng câu trả lời từ cache để giảm gọi API.'
                    : 'Sinh câu trả lời dựa trên context thật của trường.'),
            ];

            $responseTime = round(microtime(true) - $startTime, 3);

            try {
                ChatLog::create([
                    'session_id' => $sessionId,
                    'platform' => $platform,
                    'user_query' => $userMessage,
                    'bot_response' => $botReply,
                    'intent' => $analysis['intent'] ?? null,
                    'major_name' => $analysis['major'] ?? null,
                    'admission_year' => $analysis['year'] ?? null,
                    'admission_method' => $analysis['admission_method'] ?? null,
                    'score' => $analysis['score'] ?? null,
                    'province' => $analysis['province'] ?? null,
                    'entities' => $analysis['entities'] ?? [],
                    'agent_steps' => $agentSteps,
                    'retrieval_summary' => $knowledge['retrieval'] ?? [],
                    'response_time' => $responseTime,
                ]);
            } catch (\Throwable) {
                // Không để lỗi ghi log làm chết luồng chat.
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'session_id' => $sessionId,
                    'reply' => $botReply,
                    'analysis' => $analysis,
                    'agent' => [
                        'steps' => $agentSteps,
                        'cached' => $cacheHit,
                    ],
                    'rag' => $knowledge['retrieval'] ?? [],
                ],
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Chatbot đang gặp lỗi: ' . $e->getMessage(),
                'data' => [
                    'session_id' => $sessionId,
                    'reply' => 'Xin lỗi, hiện tại hệ thống chưa xử lý được câu hỏi này. Bạn thử hỏi lại rõ hơn giúp mình nhé.',
                ],
            ], 503);
        }
    }

    public function faqQuestions(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $q = trim($request->query('q', ''));
            $sessionId = trim($request->query('session_id', $request->header('X-Session-ID', '')));
            $limit = min(max((int) $request->query('limit', 12), 1), 20);
            $majorName = $this->findLatestMajorName($sessionId);

            if ($majorName) {
                return response()->json([
                    'status' => 'success',
                    'data' => $this->buildMajorSuggestedQuestions($majorName, $limit),
                ], 200);
            }

            $query = FaqQuestion::query();

            if ($q !== '') {
                $like = "%{$q}%";
                $keywords = $this->extractFaqKeywords($q);
                [$scoreSql, $scoreBindings] = $this->buildFaqRelevanceScore($like, $keywords);

                $query = $query->leftJoin('knowledge_bases', 'knowledge_bases.id', '=', 'faq_questions.knowledge_base_id')
                    ->where(function ($sub) use ($like) {
                        $sub->where('faq_questions.question', 'like', $like)
                            ->orWhere('faq_questions.category', 'like', $like)
                            ->orWhere('knowledge_bases.title', 'like', $like)
                            ->orWhere('knowledge_bases.content', 'like', $like);
                    })
                    ->select('faq_questions.question', 'faq_questions.category')
                    ->selectRaw("{$scoreSql} as relevance", $scoreBindings)
                    ->orderByDesc('relevance')
                    ->orderByDesc('faq_questions.id');
            } else {
                $query = $query->orderByDesc('id')->select('question', 'category');
            }

            $items = $query->limit($limit)->get();

            if ($items->isEmpty() && $q !== '') {
                $items = $this->searchFaqWithoutAccents($q, $limit);
            }

            $data = $items->map(function ($it) {
                return [
                    'question' => $it->question,
                    'category' => $it->category,
                ];
            })->values();

            return response()->json([
                'status' => 'success',
                'data' => $data,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Lỗi khi lấy FAQ: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function extractFaqKeywords(string $text): array
    {
        $words = preg_split('/\s+/u', mb_strtolower($text, 'UTF-8'), -1, PREG_SPLIT_NO_EMPTY);

        return collect($words)
            ->map(fn($word) => trim($word, " \t\n\r\0\x0B.,!?;:()[]{}\"'"))
            ->filter(fn($word) => mb_strlen($word, 'UTF-8') >= 3)
            ->unique()
            ->take(6)
            ->values()
            ->toArray();
    }

    private function findLatestMajorName(string $sessionId): ?string
    {
        if ($sessionId === '') {
            return null;
        }

        return ChatLog::query()
            ->where('session_id', $sessionId)
            ->whereNotNull('major_name')
            ->where('major_name', '<>', '')
            ->latest('id')
            ->value('major_name');
    }

    private function buildMajorSuggestedQuestions(string $majorName, int $limit): array
    {
        $majorLike = '%' . $majorName . '%';

        $faqItems = FaqQuestion::query()
            ->leftJoin('knowledge_bases', 'knowledge_bases.id', '=', 'faq_questions.knowledge_base_id')
            ->where(function ($sub) use ($majorLike) {
                $sub->where('faq_questions.question', 'like', $majorLike)
                    ->orWhere('faq_questions.category', 'like', $majorLike)
                    ->orWhere('knowledge_bases.title', 'like', $majorLike)
                    ->orWhere('knowledge_bases.content', 'like', $majorLike);
            })
            ->select('faq_questions.question', 'faq_questions.category')
            ->selectRaw(
                '(CASE WHEN faq_questions.question LIKE ? THEN 30 ELSE 0 END
                + CASE WHEN knowledge_bases.title LIKE ? THEN 15 ELSE 0 END
                + CASE WHEN knowledge_bases.content LIKE ? THEN 5 ELSE 0 END) as relevance',
                [$majorLike, $majorLike, $majorLike]
            )
            ->orderByDesc('relevance')
            ->orderByDesc('faq_questions.id')
            ->limit($limit)
            ->get()
            ->map(fn ($item) => [
                'question' => $item->question,
                'category' => $item->category,
            ]);

        $templateItems = collect([
            ['question' => "Điểm chuẩn ngành {$majorName} các năm gần đây là bao nhiêu?", 'category' => 'diem_chuan'],
            ['question' => "Học phí ngành {$majorName} là bao nhiêu?", 'category' => 'hoc_phi'],
            ['question' => "Ngành {$majorName} xét tuyển những tổ hợp môn nào?", 'category' => 'to_hop'],
            ['question' => "Chỉ tiêu tuyển sinh ngành {$majorName} năm nay là bao nhiêu?", 'category' => 'chi_tieu'],
            ['question' => "Mã ngành {$majorName} là gì?", 'category' => 'ma_nganh'],
            ['question' => "Học ngành {$majorName} ra trường làm công việc gì?", 'category' => 'co_hoi_viec_lam'],
            ['question' => "Chương trình đào tạo ngành {$majorName} học những gì?", 'category' => 'chuong_trinh_dao_tao'],
            ['question' => "Ngành {$majorName} có phù hợp với năng lực của em không?", 'category' => 'tu_van_nganh'],
        ]);

        return $faqItems
            ->concat($templateItems)
            ->unique('question')
            ->take($limit)
            ->values()
            ->all();
    }

    private function buildChatCacheKey(string $message, array $knowledge, array $analysis, array $history): string
    {
        $payload = [
            'message' => Str::ascii(mb_strtolower(trim($message), 'UTF-8')),
            'context_hash' => hash('sha256', (string) ($knowledge['context'] ?? '')),
            'analysis' => [
                'intent' => $analysis['intent'] ?? null,
                'major' => $analysis['major'] ?? null,
                'year' => $analysis['year'] ?? null,
                'admission_method' => $analysis['admission_method'] ?? null,
                'score' => $analysis['score'] ?? null,
                'province' => $analysis['province'] ?? null,
            ],
            'history' => collect($history)
                ->take(-6)
                ->map(fn ($item) => [
                    'role' => is_array($item) ? ($item['role'] ?? null) : null,
                    'text' => is_array($item) ? mb_substr((string) ($item['text'] ?? ''), 0, 500, 'UTF-8') : null,
                ])
                ->values()
                ->all(),
        ];

        return 'chatbot:answer:' . hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE));
    }

    private function buildFallbackReply(array $knowledge): string
    {
        $majorResults = $knowledge['admission_majors'] ?? [];
        $knowledgeBases = $knowledge['knowledge_bases'] ?? [];

        if (!empty($majorResults)) {
            $lines = ['Mình tìm thấy thông tin tuyển sinh liên quan như sau:'];

            foreach (array_slice($majorResults, 0, 3) as $item) {
                $subjectGroups = is_array($item['subject_groups'] ?? null)
                    ? implode(', ', $item['subject_groups'])
                    : ($item['subject_groups'] ?? '');

                $lines[] = '';
                $lines[] = '**' . trim(($item['major_name'] ?? 'Ngành') . ' ' . ($item['year'] ?? '')) . '**';
                $lines[] = '- Mã ngành: ' . ($item['major_code'] ?? 'chưa có');
                $lines[] = '- Tổ hợp xét tuyển: ' . ($subjectGroups ?: 'chưa có');
                $lines[] = '- Điểm THPT: ' . ($item['score_thpt'] ?? 'chưa có');
                $lines[] = '- Điểm học bạ: ' . ($item['score_hoc_ba'] ?? 'chưa có');
                $lines[] = '- Điểm ĐGNL: ' . ($item['score_dgnl'] ?? 'chưa có');
                $lines[] = '- Chỉ tiêu: ' . ($item['quota'] ?? 'chưa có');
                $lines[] = '- Học phí: ' . ($item['tuition_fee'] ?? 'chưa có');

                if (!empty($item['description'])) {
                    $lines[] = '- Mô tả/chương trình: ' . $item['description'];
                }

                if (!empty($item['career_paths'])) {
                    $lines[] = '- Cơ hội việc làm: ' . $item['career_paths'];
                }

                if (!empty($item['source_url'])) {
                    $lines[] = '- Nguồn: ' . $item['source_url'];
                }
            }

            return implode("\n", $lines);
        }

        if (!empty($knowledgeBases)) {
            $first = $knowledgeBases[0];
            $content = trim(mb_substr((string) ($first['content'] ?? ''), 0, 1200, 'UTF-8'));
            $source = trim((string) ($first['url'] ?? ''));

            if ($source !== '') {
                $content = trim($content . "\n\nNguồn: {$source}");
            }

            return trim("Mình tìm thấy phần dữ liệu liên quan sau:\n\n"
                . (($first['title'] ?? '') ? "**{$first['title']}**\n" : '')
                . ($content ?: 'Hệ thống chưa có nội dung chi tiết phù hợp.'));
        }

        return 'Mình chưa tìm thấy dữ liệu phù hợp để trả lời chính xác câu hỏi này. Bạn thử hỏi rõ hơn theo tên ngành, năm tuyển sinh hoặc phương thức xét tuyển nhé.';
    }

    private function normalizeSourceLines(string $answer): string
    {
        $answer = preg_replace('/[ \t]+(Nguồn(?:\s+tham\s+khảo)?|Source)\s*:/iu', "\n$1:", $answer) ?? $answer;
        $answer = preg_replace('/([^\n])\s*(-\s*)?(Nguồn(?:\s+tham\s+khảo)?|Source)\s*:/iu', "$1\n$3:", $answer) ?? $answer;

        return trim($answer);
    }

    private function buildFaqRelevanceScore(string $fullLike, array $keywords): array
    {
        $parts = [
            'CASE WHEN faq_questions.question LIKE ? THEN 30 ELSE 0 END',
            'CASE WHEN knowledge_bases.title LIKE ? THEN 15 ELSE 0 END',
            'CASE WHEN faq_questions.category LIKE ? THEN 8 ELSE 0 END',
            'CASE WHEN knowledge_bases.content LIKE ? THEN 3 ELSE 0 END',
        ];

        $bindings = [$fullLike, $fullLike, $fullLike, $fullLike];

        foreach ($keywords as $keyword) {
            $like = '%' . $keyword . '%';

            $parts[] = 'CASE WHEN faq_questions.question LIKE ? THEN 6 ELSE 0 END';
            $bindings[] = $like;

            $parts[] = 'CASE WHEN knowledge_bases.title LIKE ? THEN 3 ELSE 0 END';
            $bindings[] = $like;
        }

        return ['(' . implode(' + ', $parts) . ')', $bindings];
    }

    private function searchFaqWithoutAccents(string $query, int $limit): \Illuminate\Support\Collection
    {
        $normalizedQuery = Str::ascii(mb_strtolower($query, 'UTF-8'));

        return FaqQuestion::query()
            ->leftJoin('knowledge_bases', 'knowledge_bases.id', '=', 'faq_questions.knowledge_base_id')
            ->select(
                'faq_questions.question',
                'faq_questions.category',
                'knowledge_bases.title',
                'knowledge_bases.content'
            )
            ->orderByDesc('faq_questions.id')
            ->limit(300)
            ->get()
            ->map(function ($item) use ($normalizedQuery) {
                $haystack = Str::ascii(mb_strtolower(implode(' ', [
                    $item->question,
                    $item->category,
                    $item->title,
                    mb_substr((string) $item->content, 0, 1000, 'UTF-8'),
                ]), 'UTF-8'));

                $item->fallback_relevance = $this->scoreNormalizedFaqMatch($normalizedQuery, $haystack);

                return $item;
            })
            ->filter(fn($item) => $item->fallback_relevance > 0)
            ->sortByDesc('fallback_relevance')
            ->take($limit)
            ->values();
    }

    private function scoreNormalizedFaqMatch(string $needle, string $haystack): int
    {
        if ($needle === '') {
            return 0;
        }

        if (str_contains($haystack, $needle)) {
            return 100;
        }

        $score = 0;

        foreach ($this->extractFaqKeywords($needle) as $keyword) {
            if (str_contains($haystack, Str::ascii($keyword))) {
                $score += 10;
            }
        }

        return $score;
    }
}
