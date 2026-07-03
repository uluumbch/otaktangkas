<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'name', 'slug', 'description', 'icon', 'rarity', 'criteria',
    'xp_reward', 'coins_reward', 'order', 'is_hidden', 'is_active',
])]
class Achievement extends Model
{
    /** @use HasFactory<\Database\Factories\AchievementFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'criteria' => 'array',
            'is_hidden' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_achievements')
            ->withPivot(['progress', 'is_claimed', 'unlocked_at'])
            ->withTimestamps();
    }
}
