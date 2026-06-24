<?php

namespace App\Services\Ai;

use App\Enums\ChildGender;
use App\Models\GeneratedCharacter;

readonly class CharacterBibleComposer
{
    public function compose(
        string $childName,
        int $childAge,
        string $childGender,
        ?GeneratedCharacter $existing = null,
        bool $reuseExisting = true,
    ): string {
        if ($reuseExisting && $existing !== null && trim($existing->style_bible) !== '') {
            return $existing->style_bible;
        }

        $preset = ChildGender::from($childGender) === ChildGender::Boy
            ? 'Boy: chestnut hair, brown eyes, blue overalls, yellow scarf.'
            : 'Girl: brown bob, brown eyes, lavender dress, yellow scarf.';

        return "Storybook watercolor. Hero: {$childName}, age {$childAge}. {$preset} Warm pastels, consistent character, no text in image.";
    }
}
