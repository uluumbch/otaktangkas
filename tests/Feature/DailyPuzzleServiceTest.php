<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\Category;
use App\Models\DailyPuzzle;
use App\Models\DailyPuzzleAttempt;
use App\Models\Question;
use App\Models\User;
use App\Services\DailyPuzzleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyPuzzleServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DailyPuzzleService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $category = Category::factory()->create();
        foreach (['easy', 'medium', 'hard'] as $difficulty) {
            Question::factory()->count(4)->withAnswers()->create([
                'category_id' => $category->id,
                'difficulty' => $difficulty,
            ]);
        }

        $this->service = app(DailyPuzzleService::class);
    }

    protected function correctAnswerFor(DailyPuzzle $puzzle, DailyPuzzleAttempt $attempt): Answer
    {
        return $this->service->currentQuestion($puzzle, $attempt)
            ->answers->firstWhere('is_correct', true);
    }

    protected function wrongAnswerFor(DailyPuzzle $puzzle, DailyPuzzleAttempt $attempt): Answer
    {
        return $this->service->currentQuestion($puzzle, $attempt)
            ->answers->firstWhere('is_correct', false);
    }

    public function test_todays_puzzle_is_generated_on_demand_and_reused(): void
    {
        $first = $this->service->todaysPuzzle();
        $second = $this->service->todaysPuzzle();

        $this->assertTrue($first->is($second));
        $this->assertSame(today()->toDateString(), $first->puzzle_date->toDateString());
    }

    public function test_only_one_attempt_per_player_per_puzzle(): void
    {
        $puzzle = $this->service->todaysPuzzle();
        $user = User::factory()->create();

        $first = $this->service->startAttempt($puzzle, $user);
        $second = $this->service->startAttempt($puzzle, $user);

        $this->assertTrue($first->is($second));
        $this->assertSame(1, $puzzle->fresh()->attempts_count);
    }

    public function test_perfect_run_completes_with_full_rewards(): void
    {
        $puzzle = $this->service->todaysPuzzle();
        $user = User::factory()->create(['xp' => 0, 'coins' => 100]);
        $attempt = $this->service->startAttempt($puzzle, $user);

        $solutionCount = count($puzzle->puzzle_config['solution_cells']);
        $result = [];

        for ($i = 0; $i < $solutionCount; $i++) {
            $result = $this->service->processAnswer(
                $puzzle, $attempt, $user,
                $this->correctAnswerFor($puzzle, $attempt)->id,
            );
            $this->assertTrue($result['is_correct']);
        }

        $this->assertTrue($result['completed']);
        $attempt->refresh();
        $this->assertTrue($attempt->is_completed);
        $this->assertSame($solutionCount, $attempt->correct_answers);
        $this->assertSame($puzzle->xp_reward, $attempt->xp_earned);
        $this->assertSame($puzzle->coins_reward, $attempt->coins_earned);
        $this->assertSame($puzzle->xp_reward, $user->fresh()->xp);
        $this->assertSame(100 + $puzzle->coins_reward, $user->fresh()->coins);
        $this->assertSame(1, $puzzle->fresh()->completed_count);

        // Perfect accuracy -> score of 1000 plus the full time bonus band.
        $this->assertGreaterThan(1000, $attempt->score);
    }

    public function test_wrong_answer_allows_retry_but_costs_accuracy(): void
    {
        $puzzle = $this->service->todaysPuzzle();
        $user = User::factory()->create(['xp' => 0]);
        $attempt = $this->service->startAttempt($puzzle, $user);

        $result = $this->service->processAnswer(
            $puzzle, $attempt, $user,
            $this->wrongAnswerFor($puzzle, $attempt)->id,
        );

        $this->assertFalse($result['is_correct']);
        $this->assertFalse($result['completed']);
        $attempt->refresh();
        $this->assertSame(0, $attempt->correct_answers);
        $this->assertSame(1, $attempt->moves_used);

        // Finish the puzzle correctly; rewards are scaled by accuracy.
        $solutionCount = count($puzzle->puzzle_config['solution_cells']);
        for ($i = 0; $i < $solutionCount; $i++) {
            $this->service->processAnswer(
                $puzzle, $attempt, $user,
                $this->correctAnswerFor($puzzle, $attempt)->id,
            );
        }

        $attempt->refresh();
        $this->assertTrue($attempt->is_completed);
        $this->assertLessThan($puzzle->xp_reward, $attempt->xp_earned);
        $this->assertGreaterThan(0, $attempt->xp_earned);
    }

    public function test_expired_attempt_rejects_answers(): void
    {
        $puzzle = $this->service->todaysPuzzle();
        $user = User::factory()->create();
        $attempt = $this->service->startAttempt($puzzle, $user);

        // Backdate the attempt past the time limit.
        $attempt->created_at = now()->subSeconds($puzzle->time_limit + 5);
        $attempt->save();

        $this->assertTrue($this->service->hasExpired($puzzle, $attempt));

        $result = $this->service->processAnswer(
            $puzzle, $attempt, $user,
            $this->correctAnswerFor($puzzle, $attempt)->id,
        );

        $this->assertTrue($result['expired']);
        $this->assertFalse($result['is_correct']);
        $this->assertFalse($attempt->fresh()->is_completed);
    }

    public function test_completed_attempt_rejects_further_answers(): void
    {
        $puzzle = $this->service->todaysPuzzle();
        $user = User::factory()->create();
        $attempt = $this->service->startAttempt($puzzle, $user);

        $solutionCount = count($puzzle->puzzle_config['solution_cells']);
        for ($i = 0; $i < $solutionCount; $i++) {
            $this->service->processAnswer(
                $puzzle, $attempt, $user,
                $this->correctAnswerFor($puzzle, $attempt)->id,
            );
        }

        $xpAfter = $user->fresh()->xp;
        $anyAnswer = Answer::first();
        $result = $this->service->processAnswer($puzzle, $attempt->fresh(), $user, $anyAnswer->id);

        $this->assertTrue($result['completed']);
        $this->assertFalse($result['is_correct']);
        $this->assertSame($xpAfter, $user->fresh()->xp); // no double rewards
    }

    public function test_ranking_orders_by_score_then_time(): void
    {
        $puzzle = $this->service->todaysPuzzle();

        $make = function (string $name, int $score, int $time) use ($puzzle) {
            DailyPuzzleAttempt::create([
                'daily_puzzle_id' => $puzzle->id,
                'user_id' => User::factory()->create(['name' => $name])->id,
                'is_completed' => true,
                'score' => $score,
                'time_taken' => $time,
            ]);
        };

        $make('Slow', 900, 100);
        $make('Best', 1100, 60);
        $make('FastTie', 900, 40);
        DailyPuzzleAttempt::create([
            'daily_puzzle_id' => $puzzle->id,
            'user_id' => User::factory()->create(['name' => 'Unfinished'])->id,
            'is_completed' => false,
            'score' => 9999,
        ]);

        $names = $this->service->ranking($puzzle)->map(fn ($a) => $a->user->name)->all();

        $this->assertSame(['Best', 'FastTie', 'Slow'], $names);
    }
}
