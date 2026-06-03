<?php

namespace App\Services\Ai\Data;

readonly class StoryTextGenerationInput
{
    /**
     * @param  list<string>  $sceneInstructions
     */
    public function __construct(
        public string $promptText,
        public string $childName,
        public int $childAge,
        public string $childGoal,
        public array $sceneInstructions,
        public int $pageCount,
    ) {}
}
