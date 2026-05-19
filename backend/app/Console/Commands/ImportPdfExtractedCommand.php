<?php

namespace App\Console\Commands;

use App\Models\KnowledgeBase;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ImportPdfExtractedCommand extends Command
{
    protected $signature = 'import:pdf-extracted {file} {--fresh : Xóa dữ liệu PDF cũ trước khi import}';

    protected $description = 'Import extracted PDF JSON into knowledge_bases';

    public function handle()
    {
        $path = $this->argument('file');

        if (!File::exists($path)) {
            $this->error("File không tồn tại: {$path}");
            return self::FAILURE;
        }

        $items = json_decode(File::get($path), true);

        if (!is_array($items)) {
            $this->error('JSON không hợp lệ');
            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            KnowledgeBase::where('category', 'pdf')->delete();
            $this->info('Đã xóa dữ liệu PDF cũ.');
        }

        $count = 0;
        $skipped = 0;

        foreach ($items as $item) {
            $chunks = $item['chunks'] ?? [];

            if (empty($chunks) && !empty($item['content'])) {
                $chunks = [$item['content']];
            }

            foreach ($chunks as $chunkIndex => $chunk) {
                $chunk = trim((string) $chunk);

                if (mb_strlen($chunk, 'UTF-8') < 80) {
                    $skipped++;
                    continue;
                }

                KnowledgeBase::updateOrCreate(
                    [
                        'url' => ($item['file_path'] ?? 'unknown') . '#chunk-' . ($chunkIndex + 1),
                    ],
                    [
                        'category' => 'pdf',
                        'source_type' => $item['source_type'] ?? 'pdf',
                        'title' => ($item['file_name'] ?? 'PDF') . ' - chunk ' . ($chunkIndex + 1),
                        'content' => $chunk,
                        'published_at' => $this->safePublishedAt($item['year'] ?? null),
                    ]
                );

                $count++;
            }
        }

        $this->info("Import thành công {$count} chunks.");
        $this->info("Bỏ qua {$skipped} chunks quá ngắn.");

        return self::SUCCESS;
    }

    private function safePublishedAt($year): ?string
    {
        if (!$year) {
            return null;
        }

        if (is_array($year)) {
            $year = reset($year);
        }

        $year = (int) $year;

        if ($year < 2020 || $year > 2026) {
            return null;
        }

        return "{$year}-01-01 00:00:00";
    }
}