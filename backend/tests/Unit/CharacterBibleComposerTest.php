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

        $result = $composer->compose('Masha', 6, $existing);

        $this->assertSame('Existing style bible for Masha.', $result);
    }

    public function test_builds_new_style_bible_when_character_missing(): void
    {
        $composer = new CharacterBibleComposer;

        $result = $composer->compose('Masha', 6, null);

        $this->assertStringContainsString('Masha', $result);
        $this->assertStringContainsString('age 6', $result);
    }
}
