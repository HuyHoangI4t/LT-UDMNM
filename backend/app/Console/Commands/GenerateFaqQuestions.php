<?php

namespace App\Console\Commands;

use App\Models\FaqQuestion;
use App\Models\KnowledgeBase;
use App\Services\FaqGeneratorService;
use Illuminate\Console\Command;

class GenerateFaqQuestions extends Command
{
    protected $signature = 'faq:generate {--fresh} {--limit=50}';

    protected $description = 'Generate FAQ questions from knowledge_bases using Gemini';

    public function handle(FaqGeneratorService $faqGenerator): int
    {
        if ($this->option('fresh')) {
            FaqQuestion::truncate();
            $this->info('Đã xóa FAQ cũ.');
        }

        $limit = (int) $this->option('limit');

        $items = KnowledgeBase::query()
            ->whereNotNull('title')
            ->where('title', '!=', '')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $created = 0;

        foreach ($items as $item) {
            $this->info("Generate FAQ cho ID {$item->id}: {$item->title}");
            $category = trim($item->category ?? 'general') ?: 'general';

            try {
                $questions = $faqGenerator->generate(
                    $item->title,
                    $category,
                    $item->content
                );
            } catch (\Throwable $e) {
                $this->error("Lỗi Gemini ID {$item->id}: " . $e->getMessage());
                continue;
            }

            foreach ($questions as $question) {
                FaqQuestion::updateOrCreate(
                    [
                        'question' => $question,
                        'category' => $category,
                    ],
                    [
                        'source_type' => $item->source_type,
                        'knowledge_base_id' => $item->id,
                    ]
                );

                if ($question) {
                    $created++;
                }
            }

            sleep(1);
        }

        $this->info("Đã generate {$created} câu hỏi gợi ý.");

        return self::SUCCESS;
    }
}