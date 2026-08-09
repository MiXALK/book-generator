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
            500,
        );

        $this->assertLessThanOrEqual(500, mb_strlen($prompt));
        $this->assertStringContainsString('Scene:', $prompt);
        $this->assertStringContainsString('No text.', $prompt);
    }

    public function test_compose_page_prompt_preserves_plot_before_truncating_character_reference(): void
    {
        $composer = new IllustrationPromptComposer;
        $styleBible = str_repeat('Stable character trait. ', 30);
        $pageText = 'Луна заглянула в окно, а рядом с Аней стояли мама и рыжий кот.';

        $prompt = $composer->composePagePrompt(
            $styleBible,
            $pageText,
            500,
        );

        $this->assertLessThanOrEqual(500, mb_strlen($prompt));
        $this->assertStringContainsString("Scene: {$pageText}", $prompt);
        $this->assertStringContainsString('Hero:', $prompt);
        $this->assertStringContainsString('No text.', $prompt);
        $this->assertStringNotContainsString(trim($styleBible), $prompt);
    }

    public function test_compose_page_prompt_keeps_short_prompts_unchanged(): void
    {
        $composer = new IllustrationPromptComposer;

        $prompt = $composer->composePagePrompt(
            'Short style bible.',
            'Short scene.',
            500,
        );

        $this->assertSame(
            "Style: 3D CGI, soft cinematic light. No text.\n".
            "Scene: Short scene.\n".
            'Hero: Short style bible.',
            $prompt,
        );
    }

    public function test_compose_page_prompt_never_exceeds_limit_with_multibyte_input(): void
    {
        $composer = new IllustrationPromptComposer;

        $prompt = $composer->composePagePrompt(
            str_repeat('Одинаковый герой. ', 30),
            str_repeat('Очень длинная сцена. ', 30),
            500,
        );

        $this->assertLessThanOrEqual(500, mb_strlen($prompt));
        $this->assertStringContainsString('Scene:', $prompt);
        $this->assertStringContainsString('No text.', $prompt);
    }
}
