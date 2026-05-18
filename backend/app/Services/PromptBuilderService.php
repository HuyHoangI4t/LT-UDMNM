<?php

namespace App\Services;

class PromptBuilderService
{
    public function build(string $systemPrompt, string $context, string $question): string
    {
        return trim(implode("\n\n", [
            $systemPrompt,
            'CONTEXT:',
            $context,
            'USER QUESTION:',
            $question,
        ]));
    }
}
