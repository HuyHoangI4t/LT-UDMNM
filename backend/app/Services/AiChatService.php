<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Exception;

class AiChatService
{
    /**
     * Gửi câu hỏi lên Gemini 3 Flash Preview API
     */
    public function getAnswer($userMessage)
    {
        $apiKey = env('GEMINI_API_KEY');

        if (empty($apiKey)) {
            throw new Exception("Chưa cấu hình GEMINI_API_KEY trong file .env");
        }

        // ĐỊA CHỈ CHÍNH XÁC MÀ BẠN VỪA CURL THÀNH CÔNG
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-3-flash-preview:generateContent?key={$apiKey}";

        // Lấy quy tắc AI từ config
        $systemPrompt = config('ai-rules.system_prompt');

        try {
            $response = Http::timeout(30)
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
                                ['text' => $userMessage]
                            ]
                        ]
                    ]
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Trích xuất văn bản trả lời
                if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                    return $data['candidates'][0]['content']['parts'][0]['text'];
                }
                
                throw new Exception("Gemini không trả về văn bản. Kiểm tra lại phản hồi.");
            }

            // Xử lý lỗi từ Google
            $error = $response->json();
            $msg = $error['error']['message'] ?? 'Lỗi không xác định';
            throw new Exception("Lỗi từ Gemini: " . $msg);

        } catch (Exception $e) {
            throw new Exception("Lỗi kết nối AI: " . $e->getMessage());
        }
    }
}