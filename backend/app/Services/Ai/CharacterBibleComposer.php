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
            ? 'Default boy character preset: bright curious boy with short chestnut hair, warm brown eyes, '.
                'blue overalls, and yellow scarf.'
            : 'Default girl character preset: bright curious girl with soft brown bob hair, warm brown eyes, '.
                'lavender dress, and yellow scarf.';

        return implode(' ', [
            "Children's storybook illustration style.",
            "Main character: {$childName}, age {$childAge}.",
            $preset,
            'Soft watercolor textures, warm pastel palette, friendly rounded shapes.',
            'Consistent character appearance across all pages: same face shape, hair, and outfit.',
            'Safe, age-appropriate, no text in the image.',
        ]);
    }
}
