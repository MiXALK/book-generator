<?php

namespace App\Services\Ai;

readonly class IllustrationPromptComposer
{
    public function composePagePrompt(
        string $styleBible,
        string $pageText,
        string $pageCategory,
        int $pageNumber,
        string $childName,
    ): string {
        $scene = match ($pageCategory) {
            'cover' => "Book cover featuring {$childName} as the hero.",
            'ending' => "Happy ending scene with {$childName} celebrating.",
            default => "Story scene for page {$pageNumber}.",
        };

        return implode("\n", [
            $styleBible,
            $scene,
            "Scene context: {$pageText}",
            'Full-bleed illustration, no captions or letters.',
        ]);
    }
}
