<?php

namespace App\Livewire\Game;

use App\Enums\MatchStatus;
use App\Models\GameMatch;
use App\Services\MatchService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Pertandingan - OtakTangkas')]
class Play extends Component
{
    public int $matchId;

    public ?string $selectedPosition = null;

    public ?string $feedback = null;

    public function mount(GameMatch $match): void
    {
        abort_unless(
            in_array(auth()->id(), array_filter([$match->player1_id, $match->player2_id]), true),
            403,
        );

        $this->matchId = $match->id;
    }

    #[Computed]
    public function match(): GameMatch
    {
        return GameMatch::with(['currentQuestion.answers', 'player1', 'player2'])
            ->findOrFail($this->matchId);
    }

    public function mySymbol(): string
    {
        return auth()->id() === $this->match->player1_id
            ? $this->match->player1_symbol
            : $this->match->player2_symbol;
    }

    public function isMyTurn(): bool
    {
        return $this->match->status === MatchStatus::InProgress
            && $this->match->current_turn_user_id === auth()->id();
    }

    /**
     * Re-render when the opponent moves (Echo) or on poll fallback.
     */
    #[On('echo:match.{matchId},MatchUpdated')]
    #[On('echo:match.{matchId},MatchEnded')]
    public function refresh(): void
    {
        unset($this->match);
    }

    public function selectCell(string $position): void
    {
        if (! $this->isMyTurn()) {
            return;
        }

        [$row, $col] = array_map('intval', explode(',', $position));

        if (($this->match->board_state[$row][$col] ?? '') === '') {
            $this->selectedPosition = $position;
            $this->feedback = null;
        }
    }

    /**
     * The player ran out of time on their turn: they forfeit the match.
     */
    public function timeout(MatchService $matches): void
    {
        if ($this->isMyTurn()) {
            $matches->abandonMatch($this->match, auth()->user());
            $this->feedback = null;
            unset($this->match);
        }
    }

    public function answer(int $answerId, MatchService $matches): void
    {
        if (! $this->isMyTurn()) {
            return;
        }

        if ($this->selectedPosition === null) {
            $this->addError('board', 'Pilih kotak dulu sebelum menjawab.');

            return;
        }

        $result = $matches->processAnswer($this->match, auth()->user(), $answerId, $this->selectedPosition);
        $this->resetErrorBag();
        $this->feedback = ($result['is_correct'] ?? false) ? 'correct' : 'wrong';

        // Wrong answer keeps the turn and the selected cell for a retry.
        if ($result['success'] ?? false) {
            $this->selectedPosition = null;
        }

        unset($this->match);
    }

    public function render()
    {
        return view('livewire.game.play');
    }
}
