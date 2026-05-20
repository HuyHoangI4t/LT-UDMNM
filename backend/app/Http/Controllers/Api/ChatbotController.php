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
        tags: ['Chatbot']
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

            $botReply = $this->aiChatService->getAnswer($userMessage, $knowledge, $analysis, $history);
            $agentSteps[] = [
                'step' => 'llm_generation',
                'status' => 'completed',
                'detail' => 'Sinh câu trả lời dựa trên context thật của trường.',
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
            $limit = min(max((int) $request->query('limit', 12), 1), 20);

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
