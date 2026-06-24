<?php

namespace Tests\Unit;

use App\Models\GeneratedCharacter;
use App\Services\Ai\CharacterBibleComposer;
use Tests\TestCase;

class CharacterBibleComposerTest extends TestCase
{
    public function test_reuses_existing_character_style_bible(): void
    {
        $composer = new CharacterBibleComposer;

        $existing = new GeneratedCharacter;
        $existing->style_bible = 'Existing style bible for Masha.';

        $result = $composer->compose('Masha', 6, 'girl', $existing);

        $this->assertSame('Existing style bible for Masha.', $result);
    }

    public function test_builds_new_style_bible_when_character_missing(): void
    {
        $composer = new CharacterBibleComposer;

        $result = $composer->compose('Masha', 6, 'girl', null);

        $this->assertStringContainsString('Masha', $result);
        $this->assertStringContainsString('age 6', $result);
        $this->assertStringContainsString('Default girl character preset', $result);
    }

    public function test_builds_boy_default_preset(): void
    {
        $composer = new CharacterBibleComposer;

        $result = $composer->compose('Misha', 5, 'boy', null);

        $this->assertStringContainsString('Misha', $result);
        $this->assertStringContainsString('Default boy character preset', $result);
    }
}
