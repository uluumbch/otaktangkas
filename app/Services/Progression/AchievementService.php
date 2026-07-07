<?php

namespace App\Services\Progression;

use App\Models\Achievement;
use App\Models\User;
use Illuminate\Support\Collection;

class AchievementService
{
    /**
     * User stat columns that achievement criteria may reference.
     *
     * @var array<int, string>
     */
    private const ALLOWED_TYPES = [
        'total_matches', 'wins', 'losses', 'draws',
        'win_streak', 'best_win_streak',
        'questions_answered', 'correct_answers',
        'daily_login_streak', 'level',
    ];

    /**
     * Unlock any achievements the user newly qualifies for and award their
     * rewards. Returns the collection of newly unlocked achievements.
     *
     * @return Collection<int, Achievement>
     */
    public function evaluate(User $user): Collection
    {
        if ($user->is_guest) {
            return collect();
        }

        $alreadyUnlocked = $user->achievements()->pluck('achievements.id');

        $candidates = Achievement::where('is_active', true)
            ->whereNotIn('id', $alreadyUnlocked)
            ->get();

        return $candidates
            ->filter(fn (Achievement $a) => $this->qualifies($user, $a))
            ->each(fn (Achievement $a) => $this->unlock($user, $a))
            ->values();
    }

    private function qualifies(User $user, Achievement $achievement): bool
    {
        $type = $achievement->criteria['type'] ?? null;
        $value = $achievement->criteria['value'] ?? null;

        if (! in_array($type, self::ALLOWED_TYPES, true) || ! is_numeric($value)) {
            return false;
        }

        return (int) $user->{$type} >= (int) $value;
    }

    private function unlock(User $user, Achievement $achievement): void
    {
        $user->achievements()->attach($achievement->id, [
            'progress' => $achievement->criteria['value'] ?? 0,
            'is_claimed' => false,
            'unlocked_at' => now(),
        ]);

        if ($achievement->xp_reward > 0) {
            $user->addXp($achievement->xp_reward);
        }

        if ($achievement->coins_reward > 0) {
            $user->addCoins($achievement->coins_reward, "Achievement: {$achievement->name}", $achievement);
        }
    }
}
