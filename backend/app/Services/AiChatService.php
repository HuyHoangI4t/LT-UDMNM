<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;

class AiChatService
{
    public function getAnswer(string $userMessage, array $knowledge = [], array $analysis = [], array $history = []): string
    {
        $apiKey = env('GEMINI_API_KEY');

        if (empty($apiKey)) {
            throw new Exception('Chưa cấu hình GEMINI_API_KEY trong file .env');
        }

        $systemPrompt = config('ai-rules.system_prompt') ?: $this->buildSystemPrompt();
        $context = $knowledge['context'] ?? 'Không tìm thấy dữ liệu phù hợp trong cơ sở dữ liệu.';
        $conversationContext = $this->formatHistory($history);

        $intent = $analysis['intent'] ?? 'general';
        $major = $analysis['major'] ?? 'không xác định';
        $year = $analysis['year'] ?? 'không xác định';
        $admissionMethod = $analysis['admission_method'] ?? 'không xác định';
        $score = $analysis['score'] ?? 'không xác định';
        $province = $analysis['province'] ?? 'không xác định';
        $entities = json_encode($analysis['entities'] ?? [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $finalPrompt = "
PHÂN TÍCH CÂU HỎI:
- Intent: {$intent}
- Ngành: {$major}
- Năm: {$year}
- Phương thức xét tuyển: {$admissionMethod}
- Điểm người dùng cung cấp: {$score}
- Tỉnh/thành: {$province}
- Thực thể đã trích xuất: {$entities}

DỮ LIỆU ĐƯỢC TRUY XUẤT TỪ DATABASE:
{$context}

LỊCH SỬ HỘI THOẠI GẦN ĐÂY:
{$conversationContext}

CÂU HỎI NGƯỜI DÙNG:
{$userMessage}

YÊU CẦU TRẢ LỜI:
- Trả lời bằng tiếng Việt, rõ ràng, dễ hiểu.
- Chỉ dùng dữ liệu trong phần DATABASE và lịch sử hội thoại được cung cấp.
- Nếu dữ liệu không có, nói rõ: hiện hệ thống chưa có dữ liệu chính xác.
- Không tự bịa điểm chuẩn, học phí, chỉ tiêu, mã ngành, học bổng hoặc ký túc xá.
- Nếu câu hỏi là tư vấn chọn ngành, hãy dựa trên năng lực, sở thích, định hướng nghề nghiệp đã trích xuất; nếu thiếu thông tin, hỏi lại tối đa 1 câu ngắn.
- Nếu có nguồn thì nhắc người dùng kiểm tra thêm ở website tuyển sinh chính thức.
";

        $answer = $this->askGemini($systemPrompt, $finalPrompt);
        $answer = $this->guardAgainstUnsupportedClaims($answer, $context);

        return $this->normalizeSourceLines($answer);
    }

    public function askGemini(string $systemPrompt, string $finalPrompt): string
    {
        $apiKey = env('GEMINI_API_KEY');

        if (empty($apiKey)) {
            throw new Exception('Chưa cấu hình GEMINI_API_KEY trong file .env');
        }

        $model = env('GEMINI_MODEL', 'gemini-2.5-flash');
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        try {
            $response = Http::timeout((int) env('GEMINI_TIMEOUT', 60))
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post($url, [
                    'system_instruction' => [
                        'parts' => [
                            ['text' => $systemPrompt],
                        ],
                    ],
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $finalPrompt],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => (float) env('GEMINI_TEMPERATURE', 0.2),
                        'topP' => (float) env('GEMINI_TOP_P', 0.8),
                        'topK' => (int) env('GEMINI_TOP_K', 40),
                    ],
                ]);

            if ($response->successful()) {
                $data = $response->json();

                return $data['candidates'][0]['content']['parts'][0]['text']
                    ?? 'Xin lỗi, tôi chưa có thông tin phù hợp để trả lời câu này.';
            }

            $error = $response->json();
            $msg = $error['error']['message'] ?? 'Lỗi không xác định';

            throw new Exception('Lỗi từ Gemini: ' . $msg);
        } catch (Exception $e) {
            throw new Exception('Lỗi kết nối AI: ' . $e->getMessage());
        }
    }

    private function buildSystemPrompt(): string
    {
        return "
Bạn là chatbot tư vấn tuyển sinh của Trường Đại học Tây Nguyên.

Vai trò:
- Tư vấn ngành học, mã ngành, tổ hợp xét tuyển, chỉ tiêu, điểm chuẩn, học phí, học bổng, ký túc xá, cơ hội việc làm và chương trình đào tạo.
- Hỗ trợ thí sinh/phụ huynh hỏi đáp tự nhiên bằng tiếng Việt.
- Ưu tiên dữ liệu tuyển sinh trong database và ngữ cảnh RAG.

Quy tắc bắt buộc:
1. Không bịa dữ liệu.
2. Không tự tạo điểm chuẩn, học phí, chỉ tiêu, mã ngành nếu database không có.
3. Nếu thiếu dữ liệu, nói rõ là hệ thống chưa có thông tin chính xác.
4. Không trả lời lan man.
5. Nếu người dùng hỏi mơ hồ, hãy hỏi lại 1 câu ngắn để làm rõ.
";
    }

    private function formatHistory(array $history): string
    {
        if (empty($history)) {
            return 'Không có.';
        }

        return collect($history)
            ->take(-6)
            ->map(function ($message) {
                if (!is_array($message)) {
                    return null;
                }

                $role = ($message['role'] ?? '') === 'user' ? 'User' : 'Assistant';
                $text = trim(strip_tags((string) ($message['text'] ?? '')));

                if ($text === '') {
                    return null;
                }

                return $role . ': ' . mb_substr($text, 0, 500, 'UTF-8');
            })
            ->filter()
            ->implode("\n");
    }

    private function guardAgainstUnsupportedClaims(string $answer, string $context): string
    {
        if (str_contains($context, 'Không tìm thấy dữ liệu phù hợp')) {
            return $answer;
        }

        $claimPatterns = [
            '/\b\d{1,2}(?:[,.]\d{1,2})?\s*điểm\b/iu',
            '/\b\d{2,4}\s*chỉ\s*tiêu\b/iu',
            '/\b\d{1,3}(?:[,.]\d{3})*\s*(?:đồng|vnđ|vnd|triệu)\b/iu',
        ];

        foreach ($claimPatterns as $pattern) {
            if (!preg_match_all($pattern, $answer, $matches)) {
                continue;
            }

            foreach ($matches[0] as $claim) {
                if (!str_contains(mb_strtolower($context, 'UTF-8'), mb_strtolower($claim, 'UTF-8'))) {
                    return 'Hiện hệ thống chưa có dữ liệu chính xác để khẳng định con số này. Bạn vui lòng đối chiếu trên website tuyển sinh chính thức của trường hoặc hỏi rõ hơn theo ngành/năm/phương thức xét tuyển.';
                }
            }
        }

        return $answer;
    }

    private function normalizeSourceLines(string $answer): string
    {
        $answer = preg_replace('/[ \t]+(Nguồn(?:\s+tham\s+khảo)?|Source)\s*:/iu', "\n$1:", $answer) ?? $answer;
        $answer = preg_replace('/([^\n])\s*(-\s*)?(Nguồn(?:\s+tham\s+khảo)?|Source)\s*:/iu', "$1\n$3:", $answer) ?? $answer;

        return trim($answer);
    }
}
