<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;

class EmbeddingService
{
    public function embed(string $text): array
    {
        $apiKey = env('GEMINI_API_KEY');
        $model = env('GEMINI_EMBEDDING_MODEL', 'gemini-embedding-001');

        if (!$apiKey) {
            throw new Exception('Thiếu GEMINI_API_KEY trong .env');
        }

        $text = trim(strip_tags($text));

        if ($text === '') {
            return [];
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:embedContent?key={$apiKey}";

        $response = Http::timeout(60)->post($url, [
            'model' => "models/{$model}",
            'content' => [
                'parts' => [
                    ['text' => mb_substr($text, 0, 8000, 'UTF-8')]
                ]
            ]
        ]);

        if (!$response->successful()) {
            throw new Exception('Lỗi embedding: ' . $response->body());
        }

        return $response->json('embedding.values') ?? [];
    }

    public function cosineSimilarity(array $a, array $b): float
    {
        $dot = 0;
        $normA = 0;
        $normB = 0;

        $count = min(count($a), count($b));

        for ($i = 0; $i < $count; $i++) {
            $dot += $a[$i] * $b[$i];
            $normA += $a[$i] ** 2;
            $normB += $b[$i] ** 2;
        }

        if ($normA == 0 || $normB == 0) {
            return 0;
        }

        return $dot / (sqrt($normA) * sqrt($normB));
    }
}