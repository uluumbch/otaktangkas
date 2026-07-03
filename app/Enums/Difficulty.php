<?php

namespace App\Enums;

enum Difficulty: string
{
    case Easy = 'easy';
    case Medium = 'medium';
    case Hard = 'hard';

    /**
     * Reward multiplier applied to XP/coins for this difficulty.
     */
    public function multiplier(): float
    {
        return match ($this) {
            self::Easy => 1.0,
            self::Medium => 1.5,
            self::Hard => 2.0,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Easy => 'Mudah',
            self::Medium => 'Sedang',
            self::Hard => 'Sulit',
        };
    }
}
