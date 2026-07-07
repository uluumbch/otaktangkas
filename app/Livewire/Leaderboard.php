<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Peringkat - OtakTangkas')]
class Leaderboard extends Component
{
    /**
     * Top players by XP (then wins), excluding guest/AI accounts.
     *
     * @return Collection<int, User>
     */
    #[Computed]
    public function players(): Collection
    {
        return User::where('is_guest', false)
            ->orderByDesc('xp')
            ->orderByDesc('wins')
            ->limit(50)
            ->get(['id', 'name', 'username', 'level', 'xp', 'wins', 'rank']);
    }

    /**
     * The authenticated user's overall position (1-based).
     */
    #[Computed]
    public function myPosition(): int
    {
        $me = auth()->user();

        $ahead = User::where('is_guest', false)
            ->where(function ($q) use ($me) {
                $q->where('xp', '>', $me->xp)
                    ->orWhere(function ($q2) use ($me) {
                        $q2->where('xp', $me->xp)->where('wins', '>', $me->wins);
                    });
            })
            ->count();

        return $ahead + 1;
    }

    public function render()
    {
        return view('livewire.leaderboard');
    }
}
