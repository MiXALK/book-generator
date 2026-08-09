<?php

namespace Tests\Unit;

use App\Services\Ai\IllustrationPromptComposer;
use Tests\TestCase;

class IllustrationPromptComposerTest extends TestCase
{
    public function test_compose_page_prompt_truncates_to_yandexart_limit(): void
    {
        $composer = new IllustrationPromptComposer;
        $styleBible = str_repeat('Style bible sentence. ', 20);
        $pageText = str_repeat('Long page text. ', 40);

        $prompt = $composer->composePagePrompt(
            $styleBible,
            $pageText,
            3,
            500,
        );

        $this->assertLessThanOrEqual(500, mb_strlen($prompt));
        $this->assertStringContainsString('Plot and cast:', $prompt);
    }

    public function test_compose_page_prompt_preserves_plot_before_truncating_character_reference(): void
    {
        $composer = new IllustrationPromptComposer;
        $styleBible = str_repeat('Stable character trait. ', 30);
        $pageText = 'Луна заглянула в окно, а рядом с Аней стояли мама и рыжий кот.';

        $prompt = $composer->composePagePrompt(
            $styleBible,
            $pageText,
            2,
            500,
        );

        $this->assertLessThanOrEqual(500, mb_strlen($prompt));
        $this->assertStringContainsString("Plot and cast: {$pageText}", $prompt);
        $this->assertStringContainsString('including secondary characters', $prompt);
        $this->assertStringContainsString('Main character reference:', $prompt);
        $this->assertStringNotContainsString(trim($styleBible), $prompt);
    }

    public function test_compose_page_prompt_keeps_short_prompts_unchanged(): void
    {
        $composer = new IllustrationPromptComposer;

        $prompt = $composer->composePagePrompt(
            'Short style bible.',
            'Short scene.',
            1,
            500,
        );

        $this->assertSame(
            "Book cover scene based on the plot.\n".
            "Plot and cast: Short scene.\n".
            'Prioritize the plot and action. Include every character mentioned in the scene, including secondary characters. '.
            "Use the main-character reference only when the hero appears in the scene.\n".
            'Main character reference: Short style bible.',
            $prompt,
        );
    }

    public function test_compose_page_prompt_uses_ending_scene_for_last_page(): void
    {
        $composer = new IllustrationPromptComposer;

        $prompt = $composer->composePagePrompt(
            'Short style bible.',
            'Short scene.',
            5,
            null,
            5,
        );

        $this->assertStringContainsString('Final story scene based on the plot.', $prompt);
    }

    public function test_compose_page_prompt_never_exceeds_limit_with_multibyte_input(): void
    {
        $composer = new IllustrationPromptComposer;

        $prompt = $composer->composePagePrompt(
            str_repeat('Одинаковый герой. ', 30),
            str_repeat('Очень длинная сцена. ', 30),
            12,
            500,
            12,
        );

        $this->assertLessThanOrEqual(500, mb_strlen($prompt));
        $this->assertStringContainsString('Final story scene based on the plot.', $prompt);
    }
}
