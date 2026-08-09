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
        $this->assertStringContainsString('lavender dress', $result);
        $this->assertStringContainsString('Render exactly these face', $result);
    }

    public function test_builds_boy_default_preset(): void
    {
        $composer = new CharacterBibleComposer;

        $result = $composer->compose('Misha', 5, 'boy', null);

        $this->assertStringContainsString('Misha', $result);
        $this->assertStringContainsString('blue overalls', $result);
    }

    public function test_uses_compact_photo_appearance_instead_of_default_face(): void
    {
        $composer = new CharacterBibleComposer;
        $appearance = 'oval face, olive skin, green eyes, long curly black hair';

        $result = $composer->compose('Masha', 6, 'girl', null, false, $appearance);

        $this->assertStringContainsString($appearance, $result);
        $this->assertStringNotContainsString('brown bob', $result);
        $this->assertStringContainsString('lavender dress', $result);
    }

    public function test_ignores_existing_bible_when_reuse_is_disabled(): void
    {
        $composer = new CharacterBibleComposer;
        $existing = new GeneratedCharacter;
        $existing->style_bible = 'Old bible.';
        $appearance = str_repeat('curly hair ', 30);

        $result = $composer->compose(
            'Masha',
            6,
            'girl',
            $existing,
            false,
            $appearance,
        );

        $this->assertNotSame('Old bible.', $result);
        $this->assertStringContainsString(trim($appearance), $result);
        $this->assertStringContainsString('Main character:', $result);
        $this->assertStringContainsString('No text.', $result);
    }
}
