<?php

namespace App\Services\Ai\Data;

readonly class StoryTextGenerationInput
{
    public function __construct(
        public string $promptText,
        public string $childName,
        public int $childAge,
        public string $childGoal,
    ) {}
}
