<?php

namespace App\Console\Commands;

use App\Models\AdmissionMajor;
use Illuminate\Console\Command;

class ImportAdmissionMajors extends Command
{
    protected $signature = 'import:admission-majors {path} {--year=2026}';

    protected $description = 'Import JSON files into admission_majors table';

    public function handle(): int
    {
        $path = $this->argument('path');

        try {
            if (is_dir($path)) {
                $files = glob($path . '/*.json');

                if (!$files) {
                    $this->error('Không tìm thấy file JSON');
                    return self::FAILURE;
                }

                foreach ($files as $file) {
                    $this->importFile($file);
                }
            } else {
                $this->importFile($path);
            }

            $this->info('IMPORT HOÀN TẤT');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }

    private function importFile(string $filePath): void
    {
        $this->info("Đang import: {$filePath}");

        if (!file_exists($filePath)) {
            $this->error('File không tồn tại');
            return;
        }

        $json = file_get_contents($filePath);
        $data = json_decode($json, true);

        if (!is_array($data)) {
            $this->error("JSON lỗi: {$filePath}");
            return;
        }

        $imported = 0;
        $year = (int) $this->option('year');

        foreach ($data as $item) {
            if (!is_array($item)) {
                continue;
            }

            $majorName = $item['major_name']
                ?? $item['name']
                ?? $item['title']
                ?? null;

            if (!$majorName) {
                continue;
            }

            $majorCode = $item['major_code']
                ?? $item['code']
                ?? null;

            $subjectGroups = $item['subject_groups']
                ?? $item['group']
                ?? null;

            if (is_string($subjectGroups)) {
                $subjectGroups = preg_split('/[,;\s]+/', $subjectGroups, -1, PREG_SPLIT_NO_EMPTY);
            }

            if (!is_array($subjectGroups)) {
                $subjectGroups = [];
            }

            $lookup = [
                'year' => $item['year'] ?? $year,
            ];

            if ($majorCode) {
                $lookup['major_code'] = $majorCode;
            } else {
                $lookup['major_name'] = $majorName;
            }

            AdmissionMajor::updateOrCreate(
                $lookup,
                [
                    'major_name' => $majorName,
                    'major_code' => $majorCode,
                    'subject_groups' => array_values(array_unique(array_map('trim', $subjectGroups))),
                    'score_thpt' => $item['score_thpt'] ?? null,
                    'score_hoc_ba' => $item['score_hoc_ba'] ?? null,
                    'score_dgnl' => $item['score_dgnl'] ?? null,
                    'quota' => $item['quota'] ?? null,
                    'tuition_fee' => $item['tuition_fee'] ?? ($item['tuition'] ?? null),
                    'description' => $item['description'] ?? ($item['content'] ?? null),
                    'career_paths' => $item['career_paths'] ?? null,
                    'source_url' => $item['source_url'] ?? ($item['url'] ?? basename($filePath)),
                ]
            );

            $imported++;
        }

        $this->info("Imported: {$imported}");
    }
}
