<?php

namespace App\Services\Ai\Data;

readonly class IllustrationGenerationInput
{
    public function __construct(
        public string $prompt,
        public int $pageNumber,
    ) {}
}
