<?php

namespace App\Services;

use App\Models\AdmissionMajor;
use App\Models\KnowledgeBase;
use Illuminate\Support\Collection;

class KnowledgeSearchService
{
    public function searchSmart(string $question, array $analysis = [], int $limit = 6): array
    {
        $analysis['raw_question'] = $question;

        $majorResults = $this->searchAdmissionMajors($analysis, $limit);
        $knowledgeResults = $this->searchKnowledgeBase($question, $analysis, $limit);

        return [
            'admission_majors' => $majorResults,
            'knowledge_bases' => $knowledgeResults,
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

        $isListAllMajors =
            str_contains($rawQuestion, 'ngành nào') ||
            str_contains($rawQuestion, 'các ngành') ||
            str_contains($rawQuestion, 'danh sách ngành') ||
            str_contains($rawQuestion, 'ngành đào tạo') ||
            str_contains($rawQuestion, 'có ngành');

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
            ($analysis['intent'] ?? '')
        );

        if (empty($keywords)) {
            return [];
        }

        $query = KnowledgeBase::query();

        if (!empty($analysis['category'])) {
            $query->where('category', $analysis['category']);
        }

        if (!empty($analysis['intent'])) {
            $intentToSourceType = [
                'hoc_phi' => 'hoc_phi',
                'học phí' => 'hoc_phi',
                'hoc_bong' => 'hoc_bong',
                'học bổng' => 'hoc_bong',
                'diem_chuan' => 'diem_chuan',
                'điểm chuẩn' => 'diem_chuan',
                'ho_so' => 'ho_so',
                'hồ sơ' => 'ho_so',
                'viec_lam' => 'viec_lam',
                'việc làm' => 'viec_lam',
                'nganh_dao_tao' => 'nganh_dao_tao',
            ];

            $sourceType = $intentToSourceType[$analysis['intent']] ?? null;

            if ($sourceType) {
                $query->where('source_type', $sourceType);
            }
        }

        $query->where(function ($q) use ($keywords) {
            foreach ($keywords as $keyword) {
                $q->orWhere('title', 'like', '%' . $keyword . '%')
                    ->orWhere('content', 'like', '%' . $keyword . '%');
            }
        });

        $items = $query
            ->orderByRaw("
                CASE
                    WHEN source_type = 'nganh_dao_tao' THEN 0
                    WHEN source_type = 'diem_chuan' THEN 1
                    WHEN source_type = 'hoc_phi' THEN 2
                    WHEN source_type = 'hoc_bong' THEN 3
                    WHEN category = 'nganh_dao_tao' THEN 4
                    WHEN category = 'dai_hoc' THEN 5
                    WHEN category = 'trang_chu' THEN 6
                    ELSE 7
                END
            ")
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
            'có', 'thì', 'được', 'muốn', 'hỏi', 'những', 'các'
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
}