<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

#[Fillable([
    'name', 'email', 'password', 'username', 'avatar', 'bio',
    'xp', 'level', 'coins', 'rank',
    'preferred_language', 'settings',
    'is_guest', 'guest_expires_at',
    'is_premium', 'premium_until',
    'referral_code', 'referred_by_id',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Default attribute values, mirroring the database column defaults so
     * that helpers (addXp/addCoins) and reads work on freshly created models
     * before they are reloaded from the database.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'xp' => 0,
        'level' => 1,
        'coins' => 100,
        'rank' => 'bronze',
        'total_matches' => 0,
        'wins' => 0,
        'losses' => 0,
        'draws' => 0,
        'win_streak' => 0,
        'best_win_streak' => 0,
        'questions_answered' => 0,
        'correct_answers' => 0,
        'daily_login_streak' => 0,
        'is_premium' => false,
        'is_guest' => false,
        'preferred_language' => 'id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'settings' => 'array',
            'last_login_date' => 'date',
            'guest_expires_at' => 'datetime',
            'premium_until' => 'datetime',
            'is_guest' => 'boolean',
            'is_premium' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (! $user->username) {
                $user->username = 'user_'.Str::lower(Str::random(8));
            }
            if (! $user->referral_code) {
                $user->referral_code = Str::upper(Str::random(8));
            }
        });
    }

    // Relationships

    public function matchesAsPlayer1(): HasMany
    {
        return $this->hasMany(GameMatch::class, 'player1_id');
    }

    public function matchesAsPlayer2(): HasMany
    {
        return $this->hasMany(GameMatch::class, 'player2_id');
    }

    public function moves(): HasMany
    {
        return $this->hasMany(MatchMove::class);
    }

    public function achievements(): BelongsToMany
    {
        return $this->belongsToMany(Achievement::class, 'user_achievements')
            ->withPivot(['progress', 'is_claimed', 'unlocked_at'])
            ->withTimestamps();
    }

    public function dailyPuzzleAttempts(): HasMany
    {
        return $this->hasMany(DailyPuzzleAttempt::class);
    }

    public function friends(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'friendships', 'user_id', 'friend_id')
            ->wherePivot('status', 'accepted')
            ->withTimestamps();
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function referredUsers(): HasMany
    {
        return $this->hasMany(User::class, 'referred_by_id');
    }

    public function referredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_by_id');
    }

    /**
     * All matches this user has played, as either player.
     */
    public function matches()
    {
        return GameMatch::where('player1_id', $this->id)
            ->orWhere('player2_id', $this->id);
    }

    // Helpers

    public function addXp(int $amount): void
    {
        $this->xp += $amount;
        $this->checkLevelUp();
        $this->save();
    }

    public function addCoins(int $amount, string $description, ?object $reference = null): void
    {
        $this->coins += $amount;
        $this->save();

        $this->transactions()->create([
            'type' => $amount >= 0 ? 'earned' : 'spent',
            'amount' => $amount,
            'balance_after' => $this->coins,
            'description' => $description,
            'reference_type' => $reference ? $reference::class : null,
            'reference_id' => $reference?->id,
        ]);
    }

    protected function checkLevelUp(): void
    {
        $newLevel = $this->calculateLevel($this->xp);

        if ($newLevel > $this->level) {
            $this->level = $newLevel;
            event(new \App\Events\UserLeveledUp($this, $newLevel));
        }
    }

    protected function calculateLevel(int $xp): int
    {
        // Level 1: 0 XP, Level 2: 100 XP, Level 3: 400 XP, ... capped at 50.
        return (int) min(50, floor(sqrt($xp / 100)) + 1);
    }

    public function xpToNextLevel(): int
    {
        $xpNeeded = ($this->level ** 2) * 100;

        return max(0, $xpNeeded - $this->xp);
    }

    public function winRate(): float
    {
        return $this->total_matches === 0
            ? 0.0
            : round(($this->wins / $this->total_matches) * 100, 2);
    }

    public function accuracy(): float
    {
        return $this->questions_answered === 0
            ? 0.0
            : round(($this->correct_answers / $this->questions_answered) * 100, 2);
    }

    public function isPremium(): bool
    {
        return $this->is_premium && $this->premium_until && $this->premium_until->isFuture();
    }

    public function isGuest(): bool
    {
        return (bool) $this->is_guest;
    }

    public function canPlay(): bool
    {
        if ($this->is_guest) {
            return $this->guest_expires_at && $this->guest_expires_at->isFuture();
        }

        return true;
    }
}
