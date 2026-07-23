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
            'Anna',
            500,
        );

        $this->assertLessThanOrEqual(500, mb_strlen($prompt));
        $this->assertStringContainsString('Scene context:', $prompt);
        $this->assertStringContainsString('Full-bleed illustration, no captions or letters.', $prompt);
    }

    public function test_compose_page_prompt_preserves_plot_over_style_bible_when_limit_applied(): void
    {
        $composer = new IllustrationPromptComposer;
        $styleBible = str_repeat('Style bible sentence. ', 20);
        $pageText = 'Луна заглянула в окно и тихо прошептала: спи спокойно, моя хорошая.';

        $prompt = $composer->composePagePrompt(
            $styleBible,
            $pageText,
            2,
            'Аня',
            500,
        );

        $this->assertLessThanOrEqual(500, mb_strlen($prompt));
        $this->assertStringContainsString($pageText, $prompt);
    }

    public function test_compose_page_prompt_keeps_short_prompts_unchanged(): void
    {
        $composer = new IllustrationPromptComposer;

        $prompt = $composer->composePagePrompt(
            'Short style bible.',
            'Short scene.',
            1,
            'Anna',
            500,
        );

        $this->assertSame(
            "Short style bible.\nBook cover featuring Anna as the hero.\nScene context: Short scene.\nFull-bleed illustration, no captions or letters.",
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
            'Anna',
            null,
            5,
        );

        $this->assertStringContainsString('Happy ending scene with Anna celebrating.', $prompt);
    }
}
