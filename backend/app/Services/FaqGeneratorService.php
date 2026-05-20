<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class FaqGeneratorService
{
    public function generate(string $title, ?string $category = null, ?string $content = null): array
    {
        $apiKey = env('GEMINI_API_KEY');

        if (!$apiKey) {
            return [];
        }

        $title = trim($title);
        $category = trim($category ?? 'general');
        $content = trim($content ?? '');

        $prompt = "
Bạn là AI tạo câu hỏi gợi ý cho chatbot tuyển sinh Trường Đại học Tây Nguyên.

Hãy tạo 3 câu hỏi ngắn gọn, tự nhiên, giống sinh viên hỏi thật.

Quy tắc:
- Chỉ trả về danh sách câu hỏi
- Mỗi dòng 1 câu hỏi
- Không đánh số
- Không markdown
- Không giải thích
- Không lặp ý
- Câu hỏi phải liên quan trực tiếp đến dữ liệu

Category: {$category}

Tiêu đề: {$title}

Nội dung:
" . mb_substr($content, 0, 1500, 'UTF-8');

        $response = Http::timeout(60)->post(
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $apiKey,
            [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                        ],
                    ],
                ],
            ]
        );

        if (!$response->successful()) {
            return $this->fallbackQuestions($title, $category, $content);
        }

        $text = $response->json('candidates.0.content.parts.0.text') ?? '';
        $questions = collect(preg_split('/\r\n|\r|\n/', $text))
            ->map(fn($line) => trim($line))
            ->map(fn($line) => preg_replace('/^\d+[\.\)]\s*/', '', $line))
            ->map(fn($line) => trim($line, "-• \t\n\r\0\x0B"))
            ->filter(fn($line) => mb_strlen($line, 'UTF-8') >= 8)
            ->unique()
            ->take(5)
            ->values()
            ->toArray();

        return !empty($questions) ? $questions : $this->fallbackQuestions($title, $category, $content);
    }

    private function fallbackQuestions(string $title, ?string $category = null, ?string $content = null): array
    {
        $title = trim($title);
        $category = trim($category ?? 'general');
        $content = mb_strtolower(trim($content ?? ''), 'UTF-8');

        $questions = [
            "{$title} là gì?",
            "Tôi cần biết gì về {$title}?",
            "{$title} có phù hợp với tôi không?",
            "Thông tin quan trọng về {$title} là gì?",
            "{$title} được áp dụng cho nhóm nào?",
        ];

        $keywordQuestions = [
            'học phí' => "Học phí của {$title} là bao nhiêu?",
            'điểm chuẩn' => "Điểm chuẩn của {$title} là bao nhiêu?",
            'xét tuyển' => "{$title} xét tuyển như thế nào?",
            'hồ sơ' => "Hồ sơ liên quan đến {$title} gồm những gì?",
            'thời gian' => "Thời gian đăng ký {$title} là khi nào?",
            'điều kiện' => "Điều kiện tham gia {$title} là gì?",
            'ngành' => "{$title} thuộc nhóm ngành nào?",
            'chương trình' => "Chương trình {$title} có nội dung gì nổi bật?",
            'lập trình' => "Lập trình trong {$title} học những gì?",
            'công việc' => "Sau khi tốt nghiệp {$title} có thể làm gì?",
        ];

        foreach ($keywordQuestions as $keyword => $question) {
            if (str_contains($content, $keyword)) {
                $questions[] = $question;
            }
        }

        return collect($questions)
            ->map(fn($line) => trim($line))
            ->filter(fn($line) => mb_strlen($line, 'UTF-8') >= 8)
            ->unique()
            ->take(5)
            ->values()
            ->toArray();
    }
}