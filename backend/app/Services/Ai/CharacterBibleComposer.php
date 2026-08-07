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

        $gender = ChildGender::from($childGender) === ChildGender::Boy
            ? ChildGender::Boy->value
            : ChildGender::Girl->value;

        return "Illustration in the style of Disney cartoons. Hero: {$childName}, age {$childAge}, gender {$gender}. Consistent characters, no text in image.";
    }
}
