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

        $this->assertSame(
            'Masha, 6, girl; Oval face, fair skin, green-blue eyes, long light-brown hair in side braid; dark navy blue sequined dress.',
            $result,
        );
    }

    public function test_builds_boy_default_preset(): void
    {
        $composer = new CharacterBibleComposer;

        $result = $composer->compose('Misha', 5, 'boy', null);

        $this->assertSame(
            'Misha, 5, boy; Oval face, fair skin, blue eyes, short light-brown hair; blue long plum.',
            $result,
        );
    }

    public function test_uses_compact_photo_appearance_instead_of_default_face(): void
    {
        $composer = new CharacterBibleComposer;
        $appearance = 'oval face, olive skin, green eyes, long curly black hair';

        $result = $composer->compose('Masha', 6, 'girl', null, false, $appearance);

        $this->assertStringContainsString($appearance, $result);
        $this->assertStringNotContainsString('side braid', $result);
        $this->assertStringContainsString('dark navy blue sequined dress', $result);
        $this->assertStringNotContainsString('wearing', $result);
        $this->assertStringNotContainsString('age ', $result);
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
        $this->assertStringStartsWith('Masha, 6, girl;', $result);
    }
}
