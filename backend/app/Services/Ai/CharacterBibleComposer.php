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
        ?string $appearanceProfile = null,
    ): string {
        if ($reuseExisting && $existing !== null && trim($existing->style_bible) !== '') {
            return $existing->style_bible;
        }

        $isBoy = ChildGender::from($childGender) === ChildGender::Boy;
        $gender = $isBoy ? ChildGender::Boy->value : ChildGender::Girl->value;
        $defaultAppearance = $isBoy
            ? 'Oval face, fair skin, blue eyes, short light-brown hair'
            : 'Oval face, fair skin, green-blue eyes, long light-brown hair in side braid';
        $outfit = $isBoy ? 'blue long plum' : 'dark navy blue sequined dress';
        $appearance = $this->normalizeAppearance($appearanceProfile) ?? $defaultAppearance;
        $name = trim($childName);

        return "{$name}, {$childAge}, {$gender}; {$appearance}; {$outfit}.";
    }

    private function normalizeAppearance(?string $appearance): ?string
    {
        if ($appearance === null) {
            return null;
        }

        $appearance = trim((string) preg_replace('/\s+/u', ' ', $appearance));
        $appearance = rtrim($appearance, " \t.;");

        if ($appearance === '') {
            return null;
        }

        return $appearance;
    }
}
