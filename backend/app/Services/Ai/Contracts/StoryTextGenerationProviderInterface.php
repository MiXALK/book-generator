<?php

namespace App\Services\Ai\Contracts;

use App\Services\Ai\Data\StoryTextGenerationInput;

interface StoryTextGenerationProviderInterface
{
    public function isConfigured(): bool;

    /**
     * @return list<string>|null Page texts in order, or null when generation fails.
     */
    public function generatePages(StoryTextGenerationInput $input): ?array;
}
