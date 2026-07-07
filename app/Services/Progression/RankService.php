<?php

namespace App\Services\Progression;

use App\Models\User;

class RankService
{
    /**
     * Rank tiers keyed by the minimum level required to reach them.
     *
     * @var array<int, string>
     */
    private const TIERS = [
        1 => 'bronze',
        10 => 'silver',
        20 => 'gold',
        30 => 'platinum',
        40 => 'diamond',
    ];

    /**
     * The rank a player of the given level belongs to.
     */
    public function for(int $level): string
    {
        $rank = 'bronze';

        foreach (self::TIERS as $minLevel => $tier) {
            if ($level >= $minLevel) {
                $rank = $tier;
            }
        }

        return $rank;
    }

    /**
     * Sync a user's rank to their level. Returns the new rank if it changed,
     * otherwise null.
     */
    public function sync(User $user): ?string
    {
        $rank = $this->for($user->level);

        if ($user->rank !== $rank) {
            $user->rank = $rank;
            $user->save();

            return $rank;
        }

        return null;
    }
}
