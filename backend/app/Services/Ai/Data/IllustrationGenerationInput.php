<?php

namespace App\Services\Ai\Data;

readonly class IllustrationGenerationInput
{
    public function __construct(
        public string $prompt,
        public string $childName,
        public int $childAge,
        public string $pageCategory,
        public int $pageNumber,
    ) {}
}
