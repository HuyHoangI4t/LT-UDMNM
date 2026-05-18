<?php

namespace App\Console\Commands;

use App\Models\AdmissionMajor;
use App\Models\KnowledgeBase;
use Illuminate\Console\Command;

class NormalizeAdmissionMajors extends Command
{
    protected $signature = 'admission:majors-normalize {--year=2026}';

    protected $description = 'Chuẩn hóa dữ liệu ngành từ knowledge_bases sang admission_majors';

    public function handle()
    {
        $year = (int) $this->option('year');

        $items = KnowledgeBase::where('category', 'nganh_dao_tao')->get();

        if ($items->isEmpty()) {
            $this->error('Không có dữ liệu category = nganh_dao_tao trong knowledge_bases');
            return 1;
        }

        $created = 0;
        $updated = 0;

        foreach ($items as $item) {
            $content = $this->cleanText($item->content ?? '');

            $majorName = $this->extractMajorName($item->title ?? '', $content);
            $majorCode = $this->extractMajorCode($content);
            $subjectGroups = $this->extractSubjectGroups($content);
            $careerPaths = $this->extractCareerPaths($content);
            $description = $this->extractDescription($content);

            $existing = AdmissionMajor::where('major_code', $majorCode)
                ->where('year', $year)
                ->first();

            AdmissionMajor::updateOrCreate(
                [
                    'year' => $year,
                    'major_code' => $majorCode,
                ],
                [
                    'major_name' => $majorName,
                    'subject_groups' => json_encode($subjectGroups, JSON_UNESCAPED_UNICODE),
                    'description' => $description,
                    'career_paths' => $careerPaths,
                    'source_url' => $item->url,
                ]
            );

            $existing ? $updated++ : $created++;
        }

        $this->info("Chuẩn hóa xong.");
        $this->info("Tạo mới: {$created}");
        $this->info("Cập nhật: {$updated}");

        return 0;
    }

    private function cleanText(string $text): string
    {
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\r\n?/', "\n", $text);
        $text = preg_replace('/[ \t]+/', ' ', $text);
        return trim($text);
    }

    private function extractMajorName(string $title, string $content): string
    {
        $name = trim(preg_replace('/^Ngành\s+/iu', '', $title));

        if ($name !== '') {
            return $name;
        }

        if (preg_match('/NGÀNH\s+([^\n]+)/iu', $content, $m)) {
            return trim($m[1]);
        }

        return 'Chưa rõ ngành';
    }

    private function extractMajorCode(string $content): ?string
    {
        if (preg_match('/Mã\s*ngành\s*:\s*([0-9A-Z]+)/iu', $content, $m)) {
            return trim($m[1]);
        }

        if (preg_match('/Mã\s*Ngành\s*:\s*([0-9A-Z]+)/u', $content, $m)) {
            return trim($m[1]);
        }

        return null;
    }

    private function extractSubjectGroups(string $content): array
    {
        $groups = [];

        if (preg_match('/Tổ hợp\s*(môn|xét tuyển)?\s*:\s*([A-Z0-9,\s]+)/iu', $content, $m)) {
            $groups = preg_split('/[,;\s]+/', trim($m[2]));
        }

        $groups = array_map('trim', $groups);

        $groups = array_filter($groups, function ($group) {
            return preg_match('/^[A-Z][0-9]{2}$/', $group);
        });

        return array_values(array_unique($groups));
    }

    private function extractCareerPaths(string $content): ?string
    {
        if (preg_match('/Vị trí làm việc sau khi (ra trường|tốt nghiệp)\s*:?(.*?)(Lượt xem|$)/isu', $content, $m)) {
            return trim($m[2]);
        }

        return null;
    }

    private function extractDescription(string $content): ?string
    {
        $content = preg_replace('/Lượt xem\s*:\s*[\d,.]+/iu', '', $content);

        if (preg_match('/Mã\s*ngành\s*:\s*[0-9A-Z]+\s*[–-]\s*Tổ hợp.*?\n(.*?)(Vị trí làm việc sau khi|$)/isu', $content, $m)) {
            return trim($m[1]);
        }

        return mb_substr(trim($content), 0, 2000, 'UTF-8');
    }
}