<?php

namespace App\Services\Ai\Data;

readonly class StoryTextGenerationResult
{
    public function __construct(
        public string $story,
        public ?int $promptTokens = null,
        public ?int $completionTokens = null,
    ) {}
}
