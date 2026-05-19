<?php

namespace App\Console\Commands;

use App\Models\KnowledgeBase;
use App\Services\EmbeddingService;
use Illuminate\Console\Command;

class GenerateKnowledgeEmbeddings extends Command
{
    protected $signature = 'embedding:knowledge {--limit=100} {--fresh}';

    protected $description = 'Generate embeddings for knowledge_bases';

    public function handle(EmbeddingService $embeddingService): int
    {
        $limit = (int) $this->option('limit');

        $query = KnowledgeBase::query()
            ->whereNotNull('content')
            ->where('content', '!=', '');

        if (!$this->option('fresh')) {
            $query->whereNull('embedding');
        }

        $items = $query
            ->orderBy('id')
            ->limit($limit)
            ->get();

        if ($items->isEmpty()) {
            $this->info('Không có dữ liệu cần tạo embedding.');
            return self::SUCCESS;
        }

        $success = 0;

        foreach ($items as $item) {
            try {
                $text = trim(($item->title ?? '') . "\n" . ($item->content ?? ''));

                $vector = $embeddingService->embed($text);

                if (empty($vector)) {
                    $this->warn("Bỏ qua ID {$item->id}: vector rỗng");
                    continue;
                }

                $item->embedding = json_encode($vector);
                $item->save();

                $success++;

                $this->info("OK ID {$item->id}");

                usleep(300000);
            } catch (\Throwable $e) {
                $this->error("Lỗi ID {$item->id}: " . $e->getMessage());
            }
        }

        $this->info("Tạo embedding xong: {$success}/{$items->count()}");

        return self::SUCCESS;
    }
}