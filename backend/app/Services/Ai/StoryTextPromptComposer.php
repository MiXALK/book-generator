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
        return "{$input->promptText}\n\n".
            "Child name: {$input->childName}\nChild age: {$input->childAge}\nGoal: {$input->childGoal}\n\n".
            'Return strict JSON object: {"story": "..."} with one continuous story from beginning to end. '.
            'Do not split into pages. Write 4-12 sentences. Include one gentle plot twist. '.
            'Avoid unsafe or scary content.';
    }
}
