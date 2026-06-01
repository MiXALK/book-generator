<?php

namespace App\Repositories\Contracts;

use App\Models\StoryPrompt;

interface StoryPromptRepositoryInterface
{
    public function findBestForGeneration(string $language, int $age, string $goal): ?StoryPrompt;

    public function incrementUsageCount(StoryPrompt $prompt): void;
}
