<?php

namespace App\Services\Progression;

use App\Models\User;
use Illuminate\Support\Carbon;

class StreakService
{
    /**
     * Record a login for the day and update the daily login streak:
     * consecutive days increment it, a gap resets it to 1, and repeat
     * logins on the same day are a no-op.
     *
     * Returns the current streak length.
     */
    public function recordLogin(User $user): int
    {
        $today = Carbon::today();
        $last = $user->last_login_date;

        if ($last && $last->isSameDay($today)) {
            return $user->daily_login_streak;
        }

        if ($last && $last->isSameDay($today->copy()->subDay())) {
            $user->daily_login_streak++;
        } else {
            $user->daily_login_streak = 1;
        }

        $user->last_login_date = $today;
        $user->save();

        return $user->daily_login_streak;
    }
}
