<?php

namespace App\Console\Commands;

use App\Models\AdmissionMajor;
use Illuminate\Console\Command;

class ImportAdmissionMajors extends Command
{
    protected $signature = 'import:admission-majors {path}';

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

        foreach ($data as $item) {
            if (!is_array($item)) {
                continue;
            }

            AdmissionMajor::updateOrCreate(
                [
                    'code' => $item['code'] ?? null,
                    'name' => $item['name'] ?? null,
                ],
                [
                    'group' => $item['group'] ?? null,
                    'quota' => $item['quota'] ?? null,
                    'tuition' => $item['tuition'] ?? null,
                    'category' => $item['category'] ?? null,
                    'source_file' => basename($filePath),
                ]
            );

            $imported++;
        }

        $this->info("Imported: {$imported}");
    }
}
