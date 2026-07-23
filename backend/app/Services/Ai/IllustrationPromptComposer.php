<?php

namespace App\Services\Ai;

readonly class IllustrationPromptComposer
{
    public function composePagePrompt(
        string $styleBible,
        string $pageText,
        int $pageNumber,
        string $childName,
        ?int $maxLength = null,
        ?int $totalPages = null,
    ): string {
        $scene = match (true) {
            $pageNumber === 1 => "Book cover featuring {$childName} as the hero.",
            $totalPages !== null && $pageNumber === $totalPages => "Happy ending scene with {$childName} celebrating.",
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
        $scene = trim($scene);
        $pageText = trim($pageText);
        $footer = trim($footer);
        $sceneContextPrefix = 'Scene context: ';

        $tail = $this->buildTail($scene, $sceneContextPrefix, $pageText, $footer);
        $tailLength = mb_strlen($tail);

        if ($tailLength > $maxLength) {
            $pageBudget = $maxLength
                - mb_strlen($scene)
                - mb_strlen("\n{$sceneContextPrefix}")
                - mb_strlen("\n{$footer}");

            $pageText = $this->truncateText($pageText, max(0, $pageBudget));
            $tail = $this->buildTail($scene, $sceneContextPrefix, $pageText, $footer);
            $tailLength = mb_strlen($tail);
        }

        $styleBibleBudget = $maxLength - $tailLength - ($styleBible !== '' ? 1 : 0);

        if ($styleBibleBudget <= 0 || $styleBible === '') {
            return $tail;
        }

        if (mb_strlen($styleBible) > $styleBibleBudget) {
            $styleBible = $this->truncateText($styleBible, $styleBibleBudget);
        }

        return "{$styleBible}\n{$tail}";
    }

    private function buildTail(
        string $scene,
        string $sceneContextPrefix,
        string $pageText,
        string $footer,
    ): string {
        return "{$scene}\n{$sceneContextPrefix}{$pageText}\n{$footer}";
    }

    private function truncateText(string $text, int $maxLength): string
    {
        if ($maxLength <= 0) {
            return '';
        }

        if (mb_strlen($text) <= $maxLength) {
            return $text;
        }

        if ($maxLength === 1) {
            return '…';
        }

        return rtrim(mb_substr($text, 0, $maxLength - 1)).'…';
    }
}
