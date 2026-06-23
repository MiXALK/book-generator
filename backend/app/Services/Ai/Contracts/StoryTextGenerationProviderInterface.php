<?php

namespace App\Services\Ai\Contracts;

use App\Services\Ai\Data\StoryTextGenerationInput;
use App\Services\Ai\Data\StoryTextGenerationResult;

interface StoryTextGenerationProviderInterface
{
    public function isConfigured(): bool;

    public function generateStory(StoryTextGenerationInput $input): ?StoryTextGenerationResult;
}
