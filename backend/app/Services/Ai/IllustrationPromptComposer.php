<?php

namespace App\Services\Ai;

readonly class IllustrationPromptComposer
{
    public function composePagePrompt(
        string $mainCharacter,
        string $pageText,
        ?int $maxLength = null,
    ): string {
        $style = '3D CGI, soft cinematic light. No text.';

        if ($maxLength === null) {
            return $this->buildPrompt($mainCharacter, $style, $pageText);
        }

        return $this->fitPromptToMaxLength($mainCharacter, $style, $pageText, $maxLength);
    }

    private function buildPrompt(
        string $mainCharacter,
        string $style,
        string $pageText,
    ): string {
        $parts = [
            "Style: {$style}",
            "Scene: {$pageText}",
        ];

        if ($mainCharacter !== '') {
            $parts[] = "Hero: {$mainCharacter}";
        }

        return implode("\n", $parts);
    }

    private function fitPromptToMaxLength(
        string $mainCharacter,
        string $style,
        string $pageText,
        int $maxLength,
    ): string {
        $mainCharacter = trim($mainCharacter);
        $style = trim($style);
        $pageText = trim($pageText);
        $plotPrompt = $this->buildPrompt('', $style, $pageText);

        if (mb_strlen($plotPrompt) > $maxLength) {
            $promptWithoutPlot = $this->buildPrompt('', $style, '');
            $pageBudget = $maxLength - mb_strlen($promptWithoutPlot);
            $pageText = $this->truncateText($pageText, max(0, $pageBudget));
            $plotPrompt = $this->buildPrompt('', $style, $pageText);
        }

        if (mb_strlen($plotPrompt) >= $maxLength || $mainCharacter === '') {
            return $this->truncateText($plotPrompt, $maxLength);
        }

        $heroPrefix = "\nHero: ";
        $heroBudget = $maxLength - mb_strlen($plotPrompt) - mb_strlen($heroPrefix);
        $mainCharacter = $this->truncateText($mainCharacter, max(0, $heroBudget));

        return $this->buildPrompt($mainCharacter, $style, $pageText);
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
