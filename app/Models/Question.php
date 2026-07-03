<?php

namespace App\Models;

use App\Enums\Difficulty;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'category_id', 'question', 'difficulty', 'language',
    'time_limit', 'xp_reward', 'coins_reward',
    'is_active', 'explanation', 'tags',
])]
class Question extends Model
{
    /** @use HasFactory<\Database\Factories\QuestionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'tags' => 'array',
            'difficulty' => Difficulty::class,
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }

    public function correctAnswer(): HasOne
    {
        return $this->hasOne(Answer::class)->where('is_correct', true);
    }

    public function moves(): HasMany
    {
        return $this->hasMany(MatchMove::class);
    }

    public function incrementUsage(bool $wasCorrect): void
    {
        $this->increment('times_used');
        $this->increment($wasCorrect ? 'times_correct' : 'times_incorrect');
    }

    public function difficultyMultiplier(): float
    {
        return $this->difficulty->multiplier();
    }
}
