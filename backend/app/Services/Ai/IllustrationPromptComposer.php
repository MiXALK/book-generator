<?php

namespace App\Services\Ai;

readonly class IllustrationPromptComposer
{
    public function composePagePrompt(
        string $mainCharacter,
        string $pageText,
        ?int   $maxLength = null,
    ): string {
        $scene = '3D CGI style: soft cinematic lighting.';
        $direction = 'Prioritize the plot and action. Include every character mentioned in the scene. Use the main character reference only when the main character appears in the scene.';

        if ($maxLength === null) {
            return $this->buildPrompt($mainCharacter, $scene, $pageText, $direction);
        }

        return $this->fitPromptToMaxLength($mainCharacter, $scene, $pageText, $direction, $maxLength);
    }

    private function buildPrompt(
        string $mainCharacter,
        string $scene,
        string $pageText,
        string $direction,
    ): string {
        $parts = [
            $scene,
            "Plot and cast: {$pageText}",
            $direction,
        ];

        if ($mainCharacter !== '') {
            $parts[] = $mainCharacter;
        }

        return implode("\n", $parts);
    }

    private function fitPromptToMaxLength(
        string $styleBible,
        string $scene,
        string $pageText,
        string $direction,
        int $maxLength,
    ): string {
        $styleBible = trim($styleBible);
        $scene = trim($scene);
        $pageText = trim($pageText);
        $direction = trim($direction);
        $plotPrompt = $this->buildPrompt('', $scene, $pageText, $direction);

        if (mb_strlen($plotPrompt) > $maxLength) {
            $promptWithoutPlot = $this->buildPrompt('', $scene, '', $direction);
            $pageBudget = $maxLength - mb_strlen($promptWithoutPlot);
            $pageText = $this->truncateText($pageText, max(0, $pageBudget));
            $plotPrompt = $this->buildPrompt('', $scene, $pageText, $direction);
        }

        if (mb_strlen($plotPrompt) >= $maxLength || $styleBible === '') {
            return $this->truncateText($plotPrompt, $maxLength);
        }

        $stylePrefix = "\nMain character: ";
        $styleBudget = $maxLength - mb_strlen($plotPrompt) - mb_strlen($stylePrefix);
        $styleBible = $this->truncateText($styleBible, max(0, $styleBudget));

        return $this->buildPrompt($styleBible, $scene, $pageText, $direction);
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
