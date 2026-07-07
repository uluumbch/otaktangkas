<?php

namespace App\Livewire\Puzzle;

use App\Models\DailyPuzzle;
use App\Models\DailyPuzzleAttempt;
use App\Services\DailyPuzzleService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Puzzle Harian - OtakTangkas')]
class Play extends Component
{
    public int $puzzleId;

    public ?int $attemptId = null;

    /** Transient feedback after the last answer: 'correct' | 'wrong' | null. */
    public ?string $feedback = null;

    public function mount(DailyPuzzleService $puzzles): void
    {
        $puzzle = $puzzles->todaysPuzzle();
        $this->puzzleId = $puzzle->id;

        $this->attemptId = DailyPuzzleAttempt::query()
            ->where('daily_puzzle_id', $puzzle->id)
            ->where('user_id', auth()->id())
            ->value('id');
    }

    #[Computed]
    public function puzzle(): DailyPuzzle
    {
        return DailyPuzzle::with('category')->findOrFail($this->puzzleId);
    }

    #[Computed]
    public function attempt(): ?DailyPuzzleAttempt
    {
        return $this->attemptId
            ? DailyPuzzleAttempt::findOrFail($this->attemptId)
            : null;
    }

    /**
     * Start the (single) attempt: the clock starts ticking now.
     */
    public function start(DailyPuzzleService $puzzles): void
    {
        $attempt = $puzzles->startAttempt($this->puzzle, auth()->user());
        $this->attemptId = $attempt->id;
        unset($this->attempt);
    }

    public function answer(int $answerId, DailyPuzzleService $puzzles): void
    {
        if (! $this->attempt || $this->attempt->is_completed) {
            return;
        }

        $result = $puzzles->processAnswer($this->puzzle, $this->attempt, auth()->user(), $answerId);

        $this->feedback = $result['expired']
            ? null
            : ($result['is_correct'] ? 'correct' : 'wrong');

        unset($this->attempt, $this->puzzle);
    }

    /**
     * The countdown hit zero: re-render so the expired state shows.
     */
    public function timedOut(): void
    {
        $this->feedback = null;
        unset($this->attempt);
    }

    public function render(DailyPuzzleService $puzzles)
    {
        $puzzle = $this->puzzle;
        $attempt = $this->attempt;

        $claimed = $attempt ? $puzzles->claimedCells($puzzle, $attempt) : [];
        $expired = $attempt && $puzzles->hasExpired($puzzle, $attempt);
        $inProgress = $attempt && ! $attempt->is_completed && ! $expired;

        return view('livewire.puzzle.play', [
            'claimedCells' => $claimed,
            'targetCell' => $inProgress
                ? ($puzzle->puzzle_config['solution_cells'][count($claimed)] ?? null)
                : null,
            'question' => $inProgress ? $puzzles->currentQuestion($puzzle, $attempt) : null,
            'expired' => $expired,
            'remaining' => $attempt
                ? max(0, $puzzle->time_limit - $puzzles->elapsedSeconds($attempt))
                : $puzzle->time_limit,
            'ranking' => $puzzles->ranking($puzzle),
        ]);
    }
}
