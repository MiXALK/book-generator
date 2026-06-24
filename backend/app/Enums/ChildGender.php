<?php

namespace App\Enums;

enum ChildGender: string
{
    case Boy = 'boy';
    case Girl = 'girl';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(
            fn (self $case) => $case->value,
            self::cases(),
        );
    }
}
