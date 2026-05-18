<?php

namespace App\Services;

use App\Models\KnowledgeBase;
use Illuminate\Support\Facades\Http;
use Exception;

class AiChatService
{
    public function getAnswer(string $userMessage): string
    {
        $apiKey = env('GEMINI_API_KEY');

        if (empty($apiKey)) {
            throw new Exception("Chưa cấu hình GEMINI_API_KEY trong file .env");
        }

        $systemPrompt = config('ai-rules.system_prompt');

        $normalizedQuestion = $this->normalizeUserQuestion($userMessage);

        try {
            $context = $this->buildContextFromDatabase(
                $normalizedQuestion . ' ' . $userMessage
            );
        } catch (Exception $e) {
            $context = '';
        }

        $finalPrompt = "
CONTEXT:
{$context}

USER QUESTION:
{$userMessage}
";

        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}";

        try {
            $response = Http::timeout(60)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post($url, [
                    'system_instruction' => [
                        'parts' => [
                            ['text' => $systemPrompt]
                        ]
                    ],
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $finalPrompt]
                            ]
                        ]
                    ]
                ]);

            if ($response->successful()) {
                $data = $response->json();

                return $data['candidates'][0]['content']['parts'][0]['text']
                    ?? 'Xin lỗi, tôi không có thông tin đó tại thời điểm này.';
            }

            $error = $response->json();
            $msg = $error['error']['message'] ?? 'Lỗi không xác định';

            throw new Exception("Lỗi từ Gemini: " . $msg);

        } catch (Exception $e) {
            throw new Exception("Lỗi kết nối AI: " . $e->getMessage());
        }
    }

    private function normalizeUserQuestion(string $userMessage): string
    {
        $apiKey = env('GEMINI_API_KEY');

        if (empty($apiKey)) {
            return $userMessage;
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-3-flash-preview:generateContent?key={$apiKey}";

        $prompt = "
Bạn là bộ chuẩn hóa truy vấn cho chatbot tuyển sinh Trường Đại học Tây Nguyên.

Nhiệm vụ:
- Chuyển câu hỏi người dùng thành keyword tìm kiếm ngắn gọn.
- Mở rộng từ viết tắt hoặc cách gọi phổ biến.
- Không trả lời câu hỏi.
- Chỉ trả về một dòng keyword.

Ví dụ:
cntt lấy bn điểm -> Công nghệ thông tin điểm chuẩn
ngành y xét tổ hợp nào -> Y khoa tổ hợp xét tuyển
y lấy bao nhiêu điểm -> Y khoa điểm chuẩn
thú y học khối nào -> Thú y tổ hợp xét tuyển
sp toán mã ngành -> Sư phạm Toán học mã ngành
văn bằng 2 kế toán -> Kế toán văn bằng 2
điều dưỡng khối nào -> Điều dưỡng tổ hợp xét tuyển
ngôn ngữ anh ra làm gì -> Ngôn ngữ Anh việc làm sau tốt nghiệp
26 điểm khối a00 thì được ngành nào -> ngành có tổ hợp A00 điểm chuẩn dưới 26

Câu hỏi: {$userMessage}
";

        try {
            $response = Http::timeout(20)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post($url, [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ]
                ]);

            if ($response->successful()) {
                $data = $response->json();

                return trim(
                    $data['candidates'][0]['content']['parts'][0]['text']
                    ?? $userMessage
                );
            }

            return $userMessage;

        } catch (Exception $e) {
            return $userMessage;
        }
    }

    private function buildContextFromDatabase(string $searchText): string
    {
        $keywords = $this->extractKeywords($searchText);

        try {
            if (empty($keywords)) {
                return 'Không tìm thấy dữ liệu phù hợp trong knowledge_bases.';
            }

        $titleResults = KnowledgeBase::query()
            ->where(function ($q) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $q->orWhere('title', 'LIKE', "%{$keyword}%");
                }
            })
            ->orderByRaw("
                CASE
                    WHEN category = 'nganh_dao_tao' THEN 0
                    WHEN category = 'dai_hoc' THEN 1
                    WHEN category = 'trang_chu' THEN 2
                    ELSE 3
                END
            ")
            ->latest()
            ->limit(5)
            ->get();

            if ($titleResults->isNotEmpty()) {
                $results = $titleResults;
            } else {
                $results = KnowledgeBase::query()
                    ->where(function ($q) use ($keywords) {
                        foreach ($keywords as $keyword) {
                            $q->orWhere('content', 'LIKE', "%{$keyword}%");
                        }
                    })
                    ->orderByRaw("
                        CASE
                            WHEN category = 'nganh_dao_tao' THEN 0
                            WHEN category = 'dai_hoc' THEN 1
                            WHEN category = 'trang_chu' THEN 2
                            ELSE 3
                        END
                    ")
                    ->latest()
                    ->limit(5)
                    ->get();
            }

            if ($results->isEmpty()) {
                return 'Không tìm thấy dữ liệu phù hợp trong knowledge_bases.';
            }

            $context = '';

            foreach ($results as $item) {
                $content = $this->cleanContent($item->content ?? '');

                $context .= "
TITLE: {$item->title}
CATEGORY: {$item->category}
SOURCE: [{$item->title}]({$item->url})
CONTENT:
" . mb_substr($content, 0, 2500, 'UTF-8') . "

----------------------------------------
";
            }

            return trim($context);
        } catch (Exception $e) {
            return 'Không tìm thấy dữ liệu phù hợp trong knowledge_bases.';
        }

    }

    private function extractKeywords(string $text): array
    {
        $text = mb_strtolower($text, 'UTF-8');

        $stopwords = [
            'là', 'và', 'của', 'cho', 'về', 'ở',
            'tôi', 'em', 'anh', 'chị', 'bạn',
            'bao', 'nhiêu', 'bn', 'mấy',
            'ngành', 'điểm', 'chuẩn', 'xét', 'tuyển',
            'trường', 'đại', 'học', 'tây', 'nguyên',
            'không', 'ko', 'ạ', 'ơi',
            'lấy', 'nào', 'gì', 'có', 'thì', 'được', 'đc'
        ];

        $words = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);

        $keywords = [];

        foreach ($words as $word) {
            $word = trim($word, " \t\n\r\0\x0B.,!?;:()[]{}\"'");

            if ($word === '') {
                continue;
            }

            if (in_array($word, $stopwords)) {
                continue;
            }

            if (mb_strlen($word, 'UTF-8') < 2) {
                continue;
            }

            $keywords[] = $word;
        }

        return array_values(array_unique($keywords));
    }

    private function cleanContent(string $content): string
    {
        $content = strip_tags($content);

        $content = html_entity_decode(
            $content,
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );

        $content = preg_replace('/\r\n?/', "\n", $content);
        $content = preg_replace('/\s+/', ' ', $content);

        return trim($content);
    }
}