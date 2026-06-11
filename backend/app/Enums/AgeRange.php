<?php

namespace App\Enums;

enum AgeRange: string
{
    case Toddler = 'toddler';
    case EarlyReader = 'early_reader';

    public function minAge(): int
    {
        return match ($this) {
            self::Toddler => 2,
            self::EarlyReader => 5,
        };
    }

    public function maxAge(): int
    {
        return match ($this) {
            self::Toddler => 4,
            self::EarlyReader => 7,
        };
    }

    public function contains(int $age): bool
    {
        return $age >= $this->minAge() && $age <= $this->maxAge();
    }

    /**
     * @return list<array{value: string, min_age: int, max_age: int}>
     */
    public static function catalog(): array
    {
        $entries = [];

        foreach (self::cases() as $case) {
            $entries[] = [
                'value' => $case->value,
                'min_age' => $case->minAge(),
                'max_age' => $case->maxAge(),
            ];
        }

        return $entries;
    }

    public static function fromBounds(int $minAge, int $maxAge): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->minAge() === $minAge && $case->maxAge() === $maxAge) {
                return $case;
            }
        }

        return null;
    }
}
