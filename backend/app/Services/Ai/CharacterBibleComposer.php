<?php

namespace App\Services\Ai;

use App\Models\GeneratedCharacter;

readonly class CharacterBibleComposer
{
    public function compose(string $childName, int $childAge, ?GeneratedCharacter $existing = null): string
    {
        if ($existing !== null && trim($existing->style_bible) !== '') {
            return $existing->style_bible;
        }

        return implode(' ', [
            "Children's storybook illustration style.",
            "Main character: {$childName}, age {$childAge}.",
            'Soft watercolor textures, warm pastel palette, friendly rounded shapes.',
            'Consistent character appearance across all pages: same face shape, hair, and outfit.',
            'Safe, age-appropriate, no text in the image.',
        ]);
    }
}
