<?php

namespace App\Services;

use App\Enums\Difficulty;
use App\Models\Category;
use App\Models\DailyPuzzle;
use Carbon\CarbonInterface;
use RuntimeException;

/**
 * Generates the shared daily puzzle: a preset Tic-Tac-Toe board where the
 * player must claim the remaining winning cells for X, answering a question
 * for each cell. Generation is deterministic per date so every player sees
 * the same puzzle and re-running the generator is idempotent.
 */
class PuzzleGeneratorService
{
    public function __construct(protected QuestionService $questions)
    {
    }

    /**
     * Create (or return the existing) puzzle for the given date.
     */
    public function generateForDate(CarbonInterface $date): DailyPuzzle
    {
        $existing = DailyPuzzle::query()
            ->whereDate('puzzle_date', $date->toDateString())
            ->first();

        if ($existing) {
            return $existing;
        }

        $difficulty = $this->difficultyForDate($date);
        $scenario = $this->scenarioForDate($date, $difficulty);
        $category = $this->categoryForDate($date)
            ?? throw new RuntimeException('No active categories available for the daily puzzle.');

        $questionIds = $this->pickQuestions(
            count($scenario['solution_cells']),
            $difficulty,
            $category->id,
        );

        return DailyPuzzle::create([
            'puzzle_date' => $date->toDateString(),
            'game_type' => 'tic_tac_toe',
            'category_id' => $category->id,
            'difficulty' => $difficulty->value,
            'question_ids' => $questionIds,
            'puzzle_config' => [
                'board' => $scenario['board'],
                'solution_cells' => $scenario['solution_cells'],
                'description' => $scenario['description'],
            ],
            'xp_reward' => $this->xpReward($difficulty),
            'coins_reward' => $this->coinsReward($difficulty),
            'time_limit' => $this->timeLimit($difficulty),
        ]);
    }

    /**
     * Generate puzzles for today plus the next N-1 days (pre-warming).
     *
     * @return array<int, DailyPuzzle>
     */
    public function generateUpcoming(int $days = 7): array
    {
        $puzzles = [];

        for ($i = 0; $i < $days; $i++) {
            $puzzles[] = $this->generateForDate(today()->copy()->addDays($i));
        }

        return $puzzles;
    }

    /**
     * Easy early in the week, ramping up towards the weekend.
     */
    public function difficultyForDate(CarbonInterface $date): Difficulty
    {
        return match (true) {
            $date->isoWeekday() <= 2 => Difficulty::Easy,   // Mon, Tue
            $date->isoWeekday() <= 4 => Difficulty::Medium, // Wed, Thu
            default => Difficulty::Hard,                    // Fri-Sun
        };
    }

    /**
     * Deterministically pick a board scenario for the date.
     *
     * @return array{board: array<int, ?string>, solution_cells: array<int, int>, description: string}
     */
    protected function scenarioForDate(CarbonInterface $date, Difficulty $difficulty): array
    {
        $pool = $this->scenarios()[$difficulty->value];

        return $pool[crc32($date->toDateString()) % count($pool)];
    }

    /**
     * Deterministically rotate through active categories by date.
     */
    protected function categoryForDate(CarbonInterface $date): ?Category
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        if ($categories->isEmpty()) {
            return null;
        }

        return $categories[crc32('category:'.$date->toDateString()) % $categories->count()];
    }

    /**
     * Pick question ids, widening the filter when the preferred pool is
     * too small so a puzzle can always be generated.
     *
     * @return array<int, int>
     */
    protected function pickQuestions(int $count, Difficulty $difficulty, ?int $categoryId): array
    {
        $attempts = [
            fn () => $this->questions->getQuestionsForDailyPuzzle($count, $difficulty->value, $categoryId),
            fn () => $this->questions->getQuestionsForDailyPuzzle($count, $difficulty->value),
        ];

        foreach ($attempts as $attempt) {
            $ids = $attempt();

            if (count($ids) >= $count) {
                return $ids;
            }
        }

        throw new RuntimeException(
            "Not enough active '{$difficulty->value}' questions to build the daily puzzle (need {$count})."
        );
    }

    /**
     * Preset boards. Each scenario is a flat 9-cell board (indexes 0-8,
     * row-major) plus the ordered cells the player must claim to complete
     * a winning line for X.
     *
     * @return array<string, array<int, array{board: array<int, ?string>, solution_cells: array<int, int>, description: string}>>
     */
    protected function scenarios(): array
    {
        return [
            Difficulty::Easy->value => [
                [
                    'board' => ['X', 'X', null, 'O', 'O', null, null, null, null],
                    'solution_cells' => [2],
                    'description' => 'Lengkapi baris atas untuk menang!',
                ],
                [
                    'board' => ['X', 'O', 'X', null, 'X', 'O', 'O', null, null],
                    'solution_cells' => [8],
                    'description' => 'Selesaikan diagonalnya untuk menang!',
                ],
            ],
            Difficulty::Medium->value => [
                [
                    'board' => ['X', null, null, 'O', null, 'O', null, null, null],
                    'solution_cells' => [4, 8],
                    'description' => 'Bangun diagonal dari pojok kiri atas.',
                ],
                [
                    'board' => ['O', null, null, null, 'X', null, 'O', null, 'X'],
                    'solution_cells' => [2, 5],
                    'description' => 'Kuasai kolom kanan untuk menang.',
                ],
            ],
            Difficulty::Hard->value => [
                [
                    'board' => [null, null, null, null, 'O', null, null, null, null],
                    'solution_cells' => [0, 1, 2],
                    'description' => 'Rebut seluruh baris atas dari nol.',
                ],
                [
                    'board' => ['O', null, null, null, null, null, null, null, null],
                    'solution_cells' => [2, 4, 6],
                    'description' => 'Bentuk diagonal penuh untuk menang.',
                ],
            ],
        ];
    }

    protected function xpReward(Difficulty $difficulty): int
    {
        return match ($difficulty) {
            Difficulty::Easy => 50,
            Difficulty::Medium => 75,
            Difficulty::Hard => 100,
        };
    }

    protected function coinsReward(Difficulty $difficulty): int
    {
        return match ($difficulty) {
            Difficulty::Easy => 25,
            Difficulty::Medium => 40,
            Difficulty::Hard => 60,
        };
    }

    protected function timeLimit(Difficulty $difficulty): int
    {
        return match ($difficulty) {
            Difficulty::Easy => 180,
            Difficulty::Medium => 240,
            Difficulty::Hard => 300,
        };
    }
}
