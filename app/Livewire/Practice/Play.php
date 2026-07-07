<?php

namespace App\Livewire\Practice;

use App\Enums\MatchStatus;
use App\Models\GameMatch;
use App\Services\GameEngine\GameFactory;
use App\Services\MatchService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Latihan - OtakTangkas')]
class Play extends Component
{
    public const GAME_TYPES = ['tic_tac_toe', 'connect_four'];

    public int $matchId;

    public string $gameType = 'tic_tac_toe';

    /** The board position the player has selected but not yet committed. */
    public ?string $selectedPosition = null;

    /** Transient feedback after the last answer: 'correct' | 'wrong' | null. */
    public ?string $feedback = null;

    public function mount(MatchService $matches): void
    {
        $this->startNewGame($matches);
    }

    #[Computed]
    public function match(): GameMatch
    {
        return GameMatch::with(['currentQuestion.answers', 'player1', 'player2'])
            ->findOrFail($this->matchId);
    }

    public function isMyTurn(): bool
    {
        return $this->match->status === MatchStatus::InProgress
            && $this->match->current_turn_user_id === auth()->id();
    }

    /**
     * Select a position to play; each game validates its own move format.
     */
    public function selectCell(string $position): void
    {
        if (! $this->isMyTurn()) {
            return;
        }

        $game = GameFactory::create($this->match->game_type);

        if (in_array($position, $game->getValidMoves($this->match), true)) {
            $this->selectedPosition = $position;
            $this->feedback = null;
        }
    }

    /**
     * Switch the practice game type and deal a fresh match.
     */
    public function setGameType(string $gameType, MatchService $matches): void
    {
        if (! in_array($gameType, self::GAME_TYPES, true) || $gameType === $this->gameType) {
            return;
        }

        $this->gameType = $gameType;
        $this->startNewGame($matches);
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

        // A wrong answer keeps the turn (and the selected cell) so the player
        // can try again; only a successful move clears the selection.
        if (! ($result['success'] ?? false)) {
            return;
        }

        $this->selectedPosition = null;
        unset($this->match);
        // The AI now takes its turn, but the view triggers aiTurn() after a
        // short "thinking" pause so the player sees their move land first.
    }

    /**
     * Play the AI opponent's turn. Called from the view after a brief delay.
     */
    public function aiTurn(MatchService $matches): void
    {
        if ($this->match->status === MatchStatus::InProgress
            && $this->match->current_turn_user_id === $this->match->player2_id) {
            $matches->processAIMove($this->match);
            unset($this->match);
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

    public function newGame(MatchService $matches): void
    {
        $this->startNewGame($matches);
    }

    protected function startNewGame(MatchService $matches): void
    {
        $match = $matches->createMatch(auth()->user(), 'practice', [
            'difficulty' => 'easy',
            'game_type' => $this->gameType,
        ]);
        $this->matchId = $match->id;
        $this->selectedPosition = null;
        $this->feedback = null;
        unset($this->match);
    }

    public function render()
    {
        return view('livewire.practice.play');
    }
}
