<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;

class AiChatService
{
    public function getAnswer(string $userMessage, array $knowledge = [], array $analysis = []): string
    {
        $apiKey = env('GEMINI_API_KEY');

        if (empty($apiKey)) {
            throw new Exception("Chưa cấu hình GEMINI_API_KEY trong file .env");
        }

        $systemPrompt = $this->buildSystemPrompt();
        $context = $knowledge['context'] ?? 'Không tìm thấy dữ liệu phù hợp trong cơ sở dữ liệu.';

        $intent = $analysis['intent'] ?? 'general';
        $major = $analysis['major'] ?? 'không xác định';
        $year = $analysis['year'] ?? 'không xác định';

        $finalPrompt = "
PHÂN TÍCH CÂU HỎI:
- Intent: {$intent}
- Ngành: {$major}
- Năm: {$year}

DỮ LIỆU ĐƯỢC TRUY XUẤT TỪ DATABASE:
{$context}

CÂU HỎI NGƯỜI DÙNG:
{$userMessage}

YÊU CẦU TRẢ LỜI:
- Trả lời bằng tiếng Việt, rõ ràng, dễ hiểu.
- Chỉ dùng dữ liệu trong phần DATABASE.
- Nếu dữ liệu không có, nói rõ: hiện hệ thống chưa có dữ liệu chính xác.
- Không tự bịa điểm chuẩn, học phí, chỉ tiêu, mã ngành.
- Nếu có nguồn thì nhắc người dùng kiểm tra thêm ở website tuyển sinh chính thức.
";

        return $this->askGemini($systemPrompt, $finalPrompt);
    }

    public function askGemini(string $systemPrompt, string $finalPrompt): string
    {
        $apiKey = env('GEMINI_API_KEY');

        if (empty($apiKey)) {
            throw new Exception("Chưa cấu hình GEMINI_API_KEY trong file .env");
        }

        $model = env('GEMINI_MODEL', 'gemini-2.5-flash');
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

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
                    ],
                    'generationConfig' => [
                        'temperature' => 0.2,
                        'topP' => 0.8,
                        'topK' => 40,
                    ]
                ]);

            if ($response->successful()) {
                $data = $response->json();

                return $data['candidates'][0]['content']['parts'][0]['text']
                    ?? 'Xin lỗi, tôi chưa có thông tin phù hợp để trả lời câu này.';
            }

            $error = $response->json();
            $msg = $error['error']['message'] ?? 'Lỗi không xác định';

            throw new Exception("Lỗi từ Gemini: " . $msg);

        } catch (Exception $e) {
            throw new Exception("Lỗi kết nối AI: " . $e->getMessage());
        }
    }

    private function buildSystemPrompt(): string
    {
        return "
Bạn là chatbot tư vấn tuyển sinh của Trường Đại học Tây Nguyên.

Vai trò:
- Tư vấn ngành học, điểm chuẩn, học phí, tổ hợp xét tuyển, hồ sơ, học bổng, ký túc xá.
- Trả lời thân thiện, dễ hiểu, đúng trọng tâm.
- Ưu tiên dữ liệu tuyển sinh trong database.

Quy tắc bắt buộc:
1. Không bịa dữ liệu.
2. Không tự tạo điểm chuẩn, học phí, chỉ tiêu nếu database không có.
3. Nếu thiếu dữ liệu, nói rõ là hệ thống chưa có thông tin chính xác.
4. Không trả lời lan man.
5. Nếu người dùng hỏi mơ hồ, hãy hỏi lại 1 câu ngắn để làm rõ.
";
    }
}