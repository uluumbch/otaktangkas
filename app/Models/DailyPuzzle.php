<?php

namespace App\Models;

use App\Enums\Difficulty;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'puzzle_date', 'game_type', 'category_id', 'difficulty',
    'question_ids', 'puzzle_config', 'xp_reward', 'coins_reward',
    'time_limit', 'attempts_count', 'completed_count',
])]
class DailyPuzzle extends Model
{
    /** @use HasFactory<\Database\Factories\DailyPuzzleFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'puzzle_date' => 'date',
            'question_ids' => 'array',
            'puzzle_config' => 'array',
            'difficulty' => Difficulty::class,
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(DailyPuzzleAttempt::class);
    }
}
