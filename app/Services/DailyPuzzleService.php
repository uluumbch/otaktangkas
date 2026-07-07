<?php

namespace App\Services;

use App\Models\Answer;
use App\Models\DailyPuzzle;
use App\Models\DailyPuzzleAttempt;
use App\Models\Question;
use App\Models\User;
use App\Services\Progression\AchievementService;
use Illuminate\Support\Collection;

/**
 * Runs a player's attempt at the shared daily puzzle.
 *
 * Flow: one attempt per player per puzzle. The player claims each
 * solution cell in order by answering its question; a wrong answer keeps
 * the cell open for a retry but counts against accuracy. The attempt
 * completes when every solution cell is claimed within the time limit.
 * Score = accuracy (out of 1000) + remaining seconds, so daily rankings
 * favour precision first, then speed.
 */
class DailyPuzzleService
{
    public function __construct(
        protected PuzzleGeneratorService $generator,
        protected AchievementService $achievements,
    ) {
    }

    /**
     * Today's puzzle, generating it on demand if the scheduler missed.
     */
    public function todaysPuzzle(): DailyPuzzle
    {
        return $this->generator->generateForDate(today());
    }

    /**
     * Get or start the player's single attempt for this puzzle.
     */
    public function startAttempt(DailyPuzzle $puzzle, User $user): DailyPuzzleAttempt
    {
        $attempt = DailyPuzzleAttempt::firstOrCreate([
            'daily_puzzle_id' => $puzzle->id,
            'user_id' => $user->id,
        ]);

        if ($attempt->wasRecentlyCreated) {
            $puzzle->increment('attempts_count');
        }

        return $attempt;
    }

    /**
     * Cells already claimed in this attempt, in claim order.
     *
     * @return array<int, int>
     */
    public function claimedCells(DailyPuzzle $puzzle, DailyPuzzleAttempt $attempt): array
    {
        return array_slice(
            $puzzle->puzzle_config['solution_cells'],
            0,
            $attempt->correct_answers,
        );
    }

    /**
     * The next question the player has to answer, or null when done.
     */
    public function currentQuestion(DailyPuzzle $puzzle, DailyPuzzleAttempt $attempt): ?Question
    {
        $questionId = $puzzle->question_ids[$attempt->correct_answers] ?? null;

        return $questionId
            ? Question::with('answers')->find($questionId)
            : null;
    }

    /**
     * Process an answer for the current solution cell.
     *
     * @return array{is_correct: bool, completed: bool, expired: bool}
     */
    public function processAnswer(DailyPuzzle $puzzle, DailyPuzzleAttempt $attempt, User $user, int $answerId): array
    {
        if ($attempt->is_completed || $this->hasExpired($puzzle, $attempt)) {
            return ['is_correct' => false, 'completed' => $attempt->is_completed, 'expired' => ! $attempt->is_completed];
        }

        $question = $this->currentQuestion($puzzle, $attempt);

        if (! $question) {
            return ['is_correct' => false, 'completed' => true, 'expired' => false];
        }

        $answer = Answer::where('question_id', $question->id)->findOrFail($answerId);
        $question->incrementUsage($answer->is_correct);

        $attempt->moves_used++;

        if ($answer->is_correct) {
            $attempt->correct_answers++;
        }

        $completed = $attempt->correct_answers >= count($puzzle->puzzle_config['solution_cells']);

        if ($completed) {
            $this->complete($puzzle, $attempt, $user);
        } else {
            $attempt->save();
        }

        return ['is_correct' => $answer->is_correct, 'completed' => $completed, 'expired' => false];
    }

    /**
     * Whether the attempt's time limit has run out.
     */
    public function hasExpired(DailyPuzzle $puzzle, DailyPuzzleAttempt $attempt): bool
    {
        return ! $attempt->is_completed
            && $this->elapsedSeconds($attempt) >= $puzzle->time_limit;
    }

    public function elapsedSeconds(DailyPuzzleAttempt $attempt): int
    {
        return (int) $attempt->created_at->diffInSeconds(now(), true);
    }

    /**
     * Today's ranking: completed attempts, best score first, fastest tiebreak.
     *
     * @return Collection<int, DailyPuzzleAttempt>
     */
    public function ranking(DailyPuzzle $puzzle, int $limit = 20): Collection
    {
        return DailyPuzzleAttempt::with('user')
            ->where('daily_puzzle_id', $puzzle->id)
            ->where('is_completed', true)
            ->orderByDesc('score')
            ->orderBy('time_taken')
            ->orderBy('updated_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Finalize the attempt: score it and hand out rewards.
     */
    protected function complete(DailyPuzzle $puzzle, DailyPuzzleAttempt $attempt, User $user): void
    {
        $timeTaken = min($this->elapsedSeconds($attempt), $puzzle->time_limit);
        $accuracy = $attempt->moves_used > 0
            ? $attempt->correct_answers / $attempt->moves_used
            : 0.0;

        $attempt->is_completed = true;
        $attempt->time_taken = $timeTaken;
        $attempt->score = (int) round($accuracy * 1000) + ($puzzle->time_limit - $timeTaken);

        // Rewards scale with accuracy: a perfect run earns the full pot.
        $attempt->xp_earned = (int) round($puzzle->xp_reward * $accuracy);
        $attempt->coins_earned = (int) round($puzzle->coins_reward * $accuracy);
        $attempt->save();

        $puzzle->increment('completed_count');

        if ($attempt->xp_earned > 0) {
            $user->addXp($attempt->xp_earned);
        }

        if ($attempt->coins_earned > 0) {
            $user->addCoins($attempt->coins_earned, 'Puzzle harian selesai', $puzzle);
        }

        if (! $user->isGuest()) {
            $this->achievements->evaluate($user);
        }
    }
}
