<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'daily_puzzle_id', 'user_id', 'is_completed', 'time_taken',
    'moves_used', 'correct_answers', 'score', 'xp_earned', 'coins_earned',
])]
class DailyPuzzleAttempt extends Model
{
    /** @use HasFactory<\Database\Factories\DailyPuzzleAttemptFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_completed' => 'boolean',
        ];
    }

    public function dailyPuzzle(): BelongsTo
    {
        return $this->belongsTo(DailyPuzzle::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
