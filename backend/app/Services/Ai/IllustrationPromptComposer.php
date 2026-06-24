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
        ?int $maxLength = null,
    ): string {
        $scene = match ($pageCategory) {
            'cover' => "Book cover featuring {$childName} as the hero.",
            'ending' => "Happy ending scene with {$childName} celebrating.",
            default => "Story scene for page {$pageNumber}.",
        };
        $footer = 'Full-bleed illustration, no captions or letters.';

        if ($maxLength === null) {
            return $this->buildPrompt($styleBible, $scene, $pageText, $footer);
        }

        return $this->fitPromptToMaxLength($styleBible, $scene, $pageText, $footer, $maxLength);
    }

    private function buildPrompt(
        string $styleBible,
        string $scene,
        string $pageText,
        string $footer,
    ): string {
        return implode("\n", [
            $styleBible,
            $scene,
            "Scene context: {$pageText}",
            $footer,
        ]);
    }

    private function fitPromptToMaxLength(
        string $styleBible,
        string $scene,
        string $pageText,
        string $footer,
        int $maxLength,
    ): string {
        $styleBible = trim($styleBible);
        $pageText = trim($pageText);

        while (true) {
            $prompt = $this->buildPrompt($styleBible, $scene, $pageText, $footer);

            if (mb_strlen($prompt) <= $maxLength) {
                return $prompt;
            }

            $fixedLength = mb_strlen($this->buildPrompt($styleBible, $scene, '', $footer));
            $pageBudget = $maxLength - $fixedLength - mb_strlen('Scene context: ');

            if ($pageBudget > 0 && mb_strlen($pageText) > $pageBudget) {
                $pageText = rtrim(mb_substr($pageText, 0, max(1, $pageBudget - 1))).'…';

                continue;
            }

            if (mb_strlen($styleBible) > 80) {
                $styleBible = rtrim(mb_substr($styleBible, 0, max(1, mb_strlen($styleBible) - 20))).'…';

                continue;
            }

            return rtrim(mb_substr($prompt, 0, $maxLength));
        }
    }
}
