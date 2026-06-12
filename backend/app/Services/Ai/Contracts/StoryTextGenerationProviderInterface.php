<?php

namespace App\Services\Ai\Contracts;

use App\Services\Ai\Data\StoryTextGenerationInput;

interface StoryTextGenerationProviderInterface
{
    public function isConfigured(): bool;

    public function generateStory(StoryTextGenerationInput $input): ?string;
}
