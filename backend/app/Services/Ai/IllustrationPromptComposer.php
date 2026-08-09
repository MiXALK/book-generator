<?php

namespace App\Services\Ai;

readonly class IllustrationPromptComposer
{
    public function composePagePrompt(
        string $mainCharacter,
        string $pageText,
        ?int $maxLength = null,
    ): string {
        if ($maxLength === null) {
            return $this->buildPrompt($mainCharacter, $pageText);
        }

        return $this->fitPromptToMaxLength($mainCharacter, $pageText, $maxLength);
    }

    private function buildPrompt(
        string $mainCharacter,
        string $pageText,
    ): string {
        $parts = [
            'Style: Pixar 3D CGI, soft cinematic light. No text, letters, captions, watermarks.',
            "Scene: {$pageText}",
        ];

        if ($mainCharacter !== '') {
            $parts[] = "Hero: {$mainCharacter}";
        }

        return implode("\n", $parts);
    }

    private function fitPromptToMaxLength(
        string $mainCharacter,
        string $pageText,
        int $maxLength,
    ): string {
        $mainCharacter = trim($mainCharacter);
        $pageText = trim($pageText);
        $plotPrompt = $this->buildPrompt('', $pageText);

        if (mb_strlen($plotPrompt) > $maxLength) {
            $promptWithoutPlot = $this->buildPrompt('', '');
            $pageBudget = $maxLength - mb_strlen($promptWithoutPlot);
            $pageText = $this->truncateText($pageText, max(0, $pageBudget));
            $plotPrompt = $this->buildPrompt('', $pageText);
        }

        if (mb_strlen($plotPrompt) >= $maxLength || $mainCharacter === '') {
            return $this->truncateText($plotPrompt, $maxLength);
        }

        $heroPrefix = "\nHero: ";
        $heroBudget = $maxLength - mb_strlen($plotPrompt) - mb_strlen($heroPrefix);
        $mainCharacter = $this->truncateText($mainCharacter, max(0, $heroBudget));

        return $this->buildPrompt($mainCharacter, $pageText);
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
