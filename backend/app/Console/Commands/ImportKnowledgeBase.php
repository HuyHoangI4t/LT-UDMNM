<?php

namespace App\Console\Commands;

use App\Models\KnowledgeBase;
use Illuminate\Console\Command;

class ImportKnowledgeBase extends Command
{
    protected $signature = 'import:knowledge {path}';

    protected $description = 'Import JSON files into knowledge_bases table';

    public function handle(): int
    {
        $path = $this->argument('path');

        try {

            // Nếu là folder
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

                // Nếu là file
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
            $this->error("File không tồn tại");
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

            if (!isset($item['url'])) {
                continue;
            }

            KnowledgeBase::updateOrCreate(
                [
                    'url' => $item['url']
                ],
                [
                    'category' => $item['category'] ?? null,
                    'title' => $item['title'] ?? null,
                    'content' => $item['content'] ?? null,
                    'pdf_links' => $item['pdf_links'] ?? [],
                    'image_links' => $item['image_links'] ?? [],
                ]
            );

            $imported++;
        }

        $this->info("Imported: {$imported}");
    }
}