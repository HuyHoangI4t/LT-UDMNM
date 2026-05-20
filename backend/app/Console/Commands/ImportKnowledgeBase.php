<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\KnowledgeBase;

class ImportKnowledgeBase extends Command
{
    protected $signature = 'knowledge:import {file}';

    protected $description = 'Import JSON vào knowledge_bases';

    public function handle()
    {
        $file = $this->argument('file');

        if (!file_exists($file)) {
            $this->error("Không tìm thấy file: {$file}");
            return;
        }

        $json = file_get_contents($file);

        $items = json_decode($json, true);

        if (!$items || !is_array($items)) {
            $this->error("JSON không hợp lệ");
            return;
        }

        $count = 0;
        $updated = 0;

        foreach ($items as $item) {

            $url =
                $item['url']
                ?? $item['link']
                ?? null;

            if (!$url) {
                continue;
            }

            $existing = KnowledgeBase::where('url', $url)->first();

            $content = is_array($item['content'] ?? null)
                ? json_encode($item['content'], JSON_UNESCAPED_UNICODE)
                : ($item['content'] ?? '');

            $payload = [
                'title' => $item['title'] ?? 'Không có tiêu đề',
                'content' => $content,
                'category' => $item['category'] ?? 'general',
                'source_type' => $item['source_type'] ?? ($item['category'] ?? 'general'),
                'pdf_links' => $item['pdf_links'] ?? [],
                'image_links' => $item['image_links'] ?? [],
            ];

            if (!$existing || (string) $existing->content !== (string) $content) {
                $payload['embedding'] = null;
            }

            KnowledgeBase::updateOrCreate(['url' => $url], $payload);

            if ($existing) {
                $updated++;
            } else {
                $count++;
            }
        }

        $this->info("Import thành công {$count} records.");
        $this->info("Update {$updated} records.");
    }
}
