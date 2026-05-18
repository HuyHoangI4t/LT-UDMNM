<?php

namespace App\Services;

class QuestionAnalyzerService
{
    public function analyze(string $question): array
    {
        $normalized = trim(mb_strtolower($question, 'UTF-8'));

        $categories = [
            'nganh_dao_tao',
            'dai_hoc',
            'sau_dai_hoc',
            'ngan_han',
            'vua_hoc_vua_lam',
            'faq',
        ];

        foreach ($categories as $category) {
            if (str_contains($normalized, $category)) {
                return [
                    'category' => $category,
                    'keyword' => $question,
                ];
            }
        }

        return [
            'category' => null,
            'keyword' => $question,
        ];
    }
}
