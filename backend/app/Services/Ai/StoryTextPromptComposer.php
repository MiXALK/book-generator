<?php

namespace App\Services\Ai;

use App\Services\Ai\Data\StoryTextGenerationInput;

readonly class StoryTextPromptComposer
{
    public function systemMessage(): string
    {
        return 'You are a children story writer. Keep output safe and age appropriate. Return JSON only.';
    }

    public function userMessage(StoryTextGenerationInput $input): string
    {
        $sceneBlock = '';
        if ($input->sceneInstructions !== []) {
            $sceneLines = array_map(
                fn (int $index, string $instruction) => ($index + 1).'. '.$instruction,
                array_keys($input->sceneInstructions),
                $input->sceneInstructions
            );
            $sceneBlock = "Scene guidance:\n".implode("\n", $sceneLines)."\n\n";
        }

        return "{$input->promptText}\n\n".
            "Child name: {$input->childName}\nChild age: {$input->childAge}\nGoal: {$input->childGoal}\n\n".
            $sceneBlock.
            "Return strict JSON object: {\"pages\": [\"...\", \"...\"]} with exactly {$input->pageCount} page strings.\n".
            'Each page must be a complete sentence, max 80 symbols including spaces and punctuation.'.
            ' Include one gentle plot twist. Avoid unsafe or scary content.';
    }
}
