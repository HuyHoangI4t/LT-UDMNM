<?php

namespace App\Services;

use App\Models\AdmissionMajor;
use App\Models\KnowledgeBase;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class KnowledgeSearchService
{
    public function __construct(private EmbeddingService $embeddingService)
    {
    }

    public function searchSmart(string $question, array $analysis = [], int $limit = 6): array
    {
        $analysis['raw_question'] = $question;

        $majorResults = $this->searchAdmissionMajors($analysis, $limit);
        $keywordResults = $this->searchKnowledgeBase($question, $analysis, $limit);
        $semanticResults = $this->searchByEmbedding($question, $limit, $analysis);
        $knowledgeResults = $this->mergeKnowledgeResults($keywordResults, $semanticResults, $limit);

        return [
            'admission_majors' => $majorResults,
            'knowledge_bases' => $knowledgeResults,
            'retrieval' => [
                'top_k' => $limit,
                'major_results' => count($majorResults),
                'keyword_results' => count($keywordResults),
                'semantic_results' => count($semanticResults),
                'semantic_enabled' => filter_var(env('AI_ENABLE_EMBEDDING_SEARCH', false), FILTER_VALIDATE_BOOLEAN),
                'vector_store' => env('VECTOR_STORE_DRIVER', 'local'),
                'sources' => $this->buildSources($majorResults, $knowledgeResults),
            ],
            'context' => $this->buildContext($majorResults, $knowledgeResults),
        ];
    }

    public function search(string $query, int $limit = 5): Collection
    {
        return KnowledgeBase::query()
            ->where('title', 'like', '%' . $query . '%')
            ->orWhere('content', 'like', '%' . $query . '%')
            ->latest()
            ->limit($limit)
            ->get();
    }

    private function searchAdmissionMajors(array $analysis, int $limit): array
    {
        $rawQuestion = mb_strtolower($analysis['raw_question'] ?? '', 'UTF-8');
        $normalizedRawQuestion = $this->normalizeSearchText($rawQuestion);

        $isListAllMajors = str_contains($rawQuestion, 'ngành nào') ||
            str_contains($rawQuestion, 'các ngành') ||
            str_contains($rawQuestion, 'danh sách ngành') ||
            str_contains($rawQuestion, 'ngành đào tạo') ||
            str_contains($rawQuestion, 'có ngành') ||
            str_contains($normalizedRawQuestion, 'nganh nao') ||
            str_contains($normalizedRawQuestion, 'cac nganh') ||
            str_contains($normalizedRawQuestion, 'danh sach nganh') ||
            str_contains($normalizedRawQuestion, 'nganh dao tao') ||
            str_contains($normalizedRawQuestion, 'co nganh');

        $query = AdmissionMajor::query();

        if (!$isListAllMajors && !empty($analysis['major'])) {
            $query->where('major_name', 'like', '%' . $analysis['major'] . '%');
        }

        if (!$isListAllMajors && !empty($analysis['year'])) {
            $query->where('year', $analysis['year']);
        }

        if ($isListAllMajors) {
            $items = $query
                ->orderBy('id')
                ->get()
                ->unique('major_code')
                ->values();
        } else {
            $items = $query
                ->orderByDesc('year')
                ->orderBy('id')
                ->limit($limit)
                ->get();
        }

        return $items->map(function ($item) {
            return [
                'year' => $item->year,
                'major_name' => $item->major_name,
                'major_code' => $item->major_code,
                'subject_groups' => $this->normalizeSubjectGroups($item->subject_groups),
                'score_thpt' => $item->score_thpt,
                'score_hoc_ba' => $item->score_hoc_ba,
                'score_dgnl' => $item->score_dgnl,
                'quota' => $item->quota,
                'tuition_fee' => $item->tuition_fee,
                'description' => $item->description,
                'career_paths' => $item->career_paths,
                'source_url' => $item->source_url,
            ];
        })->toArray();
    }

    private function searchKnowledgeBase(string $question, array $analysis, int $limit): array
    {
        $keywords = $this->extractKeywords(
            $question . ' ' .
            ($analysis['major'] ?? '') . ' ' .
            ($analysis['intent'] ?? '') . ' ' .
            ($analysis['admission_method'] ?? '')
        );

        if (empty($keywords)) {
            return [];
        }

        $sourceType = $this->sourceTypeForIntent($analysis['intent'] ?? null);
        $query = KnowledgeBase::query();

        $query->where(function ($q) use ($keywords) {
            foreach ($keywords as $keyword) {
                $q->orWhere('title', 'like', '%' . $keyword . '%')
                    ->orWhere('content', 'like', '%' . $keyword . '%');
            }
        });

        if (!empty($analysis['category']) || $sourceType) {
            $query->where(function ($sub) use ($analysis, $sourceType) {
                if (!empty($analysis['category'])) {
                    $sub->orWhere('category', $analysis['category'])
                        ->orWhere('source_type', $analysis['category']);
                }

                if ($sourceType) {
                    $sub->orWhere('source_type', $sourceType);
                }
            });
        }

        [$scoreSql, $scoreBindings] = $this->buildRelevanceScore($keywords, $analysis);

        $items = $query
            ->select('knowledge_bases.*')
            ->selectRaw("{$scoreSql} as relevance", $scoreBindings)
            ->orderByDesc('relevance')
            ->orderByRaw("
                CASE
                    WHEN source_type = 'nganh_dao_tao' THEN 0
                    WHEN source_type = 'diem_chuan' THEN 1
                    WHEN source_type = 'hoc_phi' THEN 2
                    WHEN source_type = 'hoc_bong' THEN 3
                    WHEN source_type = 'ky_tuc_xa' THEN 4
                    WHEN source_type = 'chuong_trinh_dao_tao' THEN 5
                    WHEN category = 'nganh_dao_tao' THEN 6
                    WHEN category = 'dai_hoc' THEN 7
                    ELSE 8
                END
            ")
            ->orderByDesc('published_at')
            ->latest()
            ->limit($limit)
            ->get();

        return $items->map(function ($item) {
            return [
                'title' => $item->title,
                'category' => $item->category,
                'source_type' => $item->source_type,
                'url' => $item->url,
                'content' => $this->cleanContent($item->content ?? ''),
            ];
        })->toArray();
    }

    private function searchByEmbedding(string $question, int $limit, array $analysis = []): array
    {
        if (!filter_var(env('AI_ENABLE_EMBEDDING_SEARCH', false), FILTER_VALIDATE_BOOLEAN)) {
            return [];
        }

        try {
            $queryVector = $this->embeddingService->embed($question);

            if (empty($queryVector)) {
                return [];
            }

            $query = KnowledgeBase::query()
                ->whereNotNull('embedding')
                ->where('embedding', '!=', '');

            $sourceType = $this->sourceTypeForIntent($analysis['intent'] ?? null);

            if (!empty($analysis['category']) || $sourceType) {
                $query->where(function ($sub) use ($analysis, $sourceType) {
                    if (!empty($analysis['category'])) {
                        $sub->orWhere('category', $analysis['category'])
                            ->orWhere('source_type', $analysis['category']);
                    }

                    if ($sourceType) {
                        $sub->orWhere('source_type', $sourceType);
                    }
                });
            }

            return $query
                ->latest()
                ->limit((int) env('EMBEDDING_LOCAL_PREFILTER_LIMIT', 200))
                ->get()
                ->map(function ($item) use ($queryVector) {
                    $vector = json_decode($item->embedding, true);

                    if (!is_array($vector)) {
                        return null;
                    }

                    return [
                        'title' => $item->title,
                        'category' => $item->category,
                        'source_type' => $item->source_type,
                        'url' => $item->url,
                        'content' => $this->cleanContent($item->content ?? ''),
                        'semantic_score' => $this->embeddingService->cosineSimilarity($queryVector, $vector),
                    ];
                })
                ->filter(fn($item) => $item && $item['semantic_score'] > 0.35)
                ->sortByDesc('semantic_score')
                ->take($limit)
                ->map(function ($item) {
                    unset($item['semantic_score']);
                    return $item;
                })
                ->values()
                ->toArray();
        } catch (\Throwable) {
            return [];
        }
    }

    private function mergeKnowledgeResults(array $keywordResults, array $semanticResults, int $limit): array
    {
        $merged = [];

        foreach (array_merge($keywordResults, $semanticResults) as $item) {
            $key = ($item['url'] ?? '') ?: (($item['title'] ?? '') . '|' . ($item['category'] ?? ''));

            if ($key === '' || isset($merged[$key])) {
                continue;
            }

            $merged[$key] = $item;
        }

        return array_slice(array_values($merged), 0, $limit);
    }

    private function buildContext(array $majorResults, array $knowledgeResults): string
    {
        $context = '';

        if (!empty($majorResults)) {
            $context .= "=== DỮ LIỆU NGÀNH TUYỂN SINH ===\n";

            foreach ($majorResults as $item) {
                $subjectGroups = is_array($item['subject_groups'])
                    ? implode(', ', $item['subject_groups'])
                    : ($item['subject_groups'] ?? '');

                $context .= "
Năm: {$item['year']}
Ngành: {$item['major_name']}
Mã ngành: {$item['major_code']}
Tổ hợp xét tuyển: {$subjectGroups}
Điểm THPT: {$item['score_thpt']}
Điểm học bạ: {$item['score_hoc_ba']}
Điểm ĐGNL: {$item['score_dgnl']}
Chỉ tiêu: {$item['quota']}
Học phí: {$item['tuition_fee']}
Mô tả/chương trình: {$item['description']}
Cơ hội việc làm: {$item['career_paths']}
Nguồn: {$item['source_url']}
-------------------------
";
            }
        }

        if (!empty($knowledgeResults)) {
            $context .= "\n=== DỮ LIỆU TRI THỨC / WEBSITE ===\n";

            foreach ($knowledgeResults as $item) {
                $content = mb_substr($item['content'], 0, 1200, 'UTF-8');

                $context .= "
Tiêu đề: {$item['title']}
Danh mục: {$item['category']}
Loại nguồn: {$item['source_type']}
Nguồn: {$item['url']}
Nội dung: {$content}
-------------------------
";
            }
        }

        if (trim($context) === '') {
            return 'Không tìm thấy dữ liệu phù hợp trong cơ sở dữ liệu.';
        }

        return trim($context);
    }

    private function extractKeywords(string $text): array
    {
        $text = mb_strtolower($text, 'UTF-8');

        $aliases = [
            'cntt' => 'công nghệ thông tin',
            'it' => 'công nghệ thông tin',
            'bn' => 'bao nhiêu',
            'ko' => 'không',
            'đc' => 'được',
            'sp' => 'sư phạm',
            'vlvh' => 'vừa học vừa làm',
        ];

        foreach ($aliases as $from => $to) {
            $text = str_replace($from, $to, $text);
        }

        $stopwords = [
            'là', 'và', 'của', 'cho', 'về', 'ở', 'tôi', 'em', 'anh', 'chị',
            'bạn', 'bao', 'nhiêu', 'ngành', 'trường', 'đại', 'học',
            'tây', 'nguyên', 'không', 'ạ', 'ơi', 'lấy', 'nào', 'gì',
            'có', 'thì', 'được', 'muốn', 'hỏi', 'những', 'các',
        ];

        $words = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        $keywords = [];

        foreach ($words as $word) {
            $word = trim($word, " \t\n\r\0\x0B.,!?;:()[]{}\"'");

            if ($word === '' || in_array($word, $stopwords, true)) {
                continue;
            }

            if (mb_strlen($word, 'UTF-8') < 2) {
                continue;
            }

            $keywords[] = $word;
        }

        return array_values(array_unique($keywords));
    }

    private function buildRelevanceScore(array $keywords, array $analysis): array
    {
        $scoreParts = [];
        $bindings = [];

        foreach (array_slice($keywords, 0, 8) as $keyword) {
            $like = '%' . $keyword . '%';

            $scoreParts[] = 'CASE WHEN title LIKE ? THEN 10 ELSE 0 END';
            $bindings[] = $like;

            $scoreParts[] = 'CASE WHEN content LIKE ? THEN 3 ELSE 0 END';
            $bindings[] = $like;
        }

        if (!empty($analysis['major'])) {
            $majorLike = '%' . $analysis['major'] . '%';

            $scoreParts[] = 'CASE WHEN title LIKE ? THEN 20 ELSE 0 END';
            $bindings[] = $majorLike;

            $scoreParts[] = 'CASE WHEN content LIKE ? THEN 6 ELSE 0 END';
            $bindings[] = $majorLike;
        }

        if (!empty($analysis['category'])) {
            $scoreParts[] = 'CASE WHEN category = ? THEN 8 ELSE 0 END';
            $bindings[] = $analysis['category'];
        }

        if (empty($scoreParts)) {
            return ['0', []];
        }

        return ['(' . implode(' + ', $scoreParts) . ')', $bindings];
    }

    private function sourceTypeForIntent(?string $intent): ?string
    {
        return [
            'hoc_phi' => 'hoc_phi',
            'hoc_bong' => 'hoc_bong',
            'diem_chuan' => 'diem_chuan',
            'ho_so' => 'ho_so',
            'co_hoi_viec_lam' => 'viec_lam',
            'chuong_trinh_dao_tao' => 'chuong_trinh_dao_tao',
            'ky_tuc_xa' => 'ky_tuc_xa',
            'nganh_dao_tao' => 'nganh_dao_tao',
        ][$intent] ?? null;
    }

    private function normalizeSearchText(string $text): string
    {
        $text = mb_strtolower(trim($text), 'UTF-8');
        $text = Str::ascii($text);
        $text = preg_replace('/[^a-z0-9\s]+/u', ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    private function normalizeSubjectGroups($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (!$value) {
            return [];
        }

        $decoded = json_decode($value, true);

        if (is_string($decoded)) {
            $decoded = json_decode($decoded, true);
        }

        if (is_array($decoded)) {
            return $decoded;
        }

        return [];
    }

    private function cleanContent(string $content): string
    {
        $content = strip_tags($content);
        $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $content = preg_replace('/\r\n?/', "\n", $content);
        $content = preg_replace('/\s+/', ' ', $content);

        return trim($content);
    }

    private function buildSources(array $majorResults, array $knowledgeResults): array
    {
        $sources = [];

        foreach ($majorResults as $item) {
            $sources[] = [
                'type' => 'admission_major',
                'title' => trim(($item['major_name'] ?? '') . ' ' . ($item['year'] ?? '')),
                'url' => $item['source_url'] ?? null,
            ];
        }

        foreach ($knowledgeResults as $item) {
            $sources[] = [
                'type' => $item['source_type'] ?? 'knowledge_base',
                'title' => $item['title'] ?? null,
                'url' => $item['url'] ?? null,
            ];
        }

        return collect($sources)
            ->filter(fn($source) => !empty($source['title']) || !empty($source['url']))
            ->unique(fn($source) => ($source['type'] ?? '') . '|' . ($source['title'] ?? '') . '|' . ($source['url'] ?? ''))
            ->values()
            ->toArray();
    }
}
