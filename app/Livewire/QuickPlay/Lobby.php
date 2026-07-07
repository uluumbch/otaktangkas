<?php

namespace App\Livewire\QuickPlay;

use App\Livewire\Practice\Play as PracticePlay;
use App\Services\MatchService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Quick Play - OtakTangkas')]
class Lobby extends Component
{
    public string $gameType = 'tic_tac_toe';

    public function setGameType(string $gameType): void
    {
        if (in_array($gameType, PracticePlay::GAME_TYPES, true)) {
            $this->gameType = $gameType;
        }
    }

    /**
     * Find a waiting opponent for the chosen game and join, or create a
     * new match and wait. Either way the player lands on the match screen.
     */
    public function findMatch(MatchService $matches)
    {
        $user = auth()->user();

        $match = $matches->findQuickPlayMatch($user, ['game_type' => $this->gameType]);

        if ($match) {
            $matches->joinMatch($match, $user);
        } else {
            $match = $matches->createMatch($user, 'quick_play', ['game_type' => $this->gameType]);
        }

        return $this->redirectRoute('match', ['match' => $match->id], navigate: true);
    }

    public function render()
    {
        return view('livewire.quick-play.lobby');
    }
}
