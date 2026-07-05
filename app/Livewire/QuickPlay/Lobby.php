<?php

namespace App\Livewire\QuickPlay;

use App\Services\MatchService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Quick Play - OtakTangkas')]
class Lobby extends Component
{
    /**
     * Find a waiting opponent and join, or create a new match and wait.
     * Either way the player lands on the match screen.
     */
    public function findMatch(MatchService $matches)
    {
        $user = auth()->user();

        $match = $matches->findQuickPlayMatch($user);

        if ($match) {
            $matches->joinMatch($match, $user);
        } else {
            $match = $matches->createMatch($user, 'quick_play');
        }

        return $this->redirectRoute('match', ['match' => $match->id], navigate: true);
    }

    public function render()
    {
        return view('livewire.quick-play.lobby');
    }
}
