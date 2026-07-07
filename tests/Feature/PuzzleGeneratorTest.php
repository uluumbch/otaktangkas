<?php

namespace Tests\Feature;

use App\Enums\Difficulty;
use App\Models\Category;
use App\Models\DailyPuzzle;
use App\Models\Question;
use App\Services\PuzzleGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PuzzleGeneratorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // A healthy question pool across every difficulty.
        $category = Category::factory()->create();
        foreach (['easy', 'medium', 'hard'] as $difficulty) {
            Question::factory()->count(4)->withAnswers()->create([
                'category_id' => $category->id,
                'difficulty' => $difficulty,
            ]);
        }
    }

    public function test_generates_a_valid_puzzle_for_a_date(): void
    {
        $date = Carbon::parse('2026-07-06'); // a Monday -> easy

        $puzzle = app(PuzzleGeneratorService::class)->generateForDate($date);

        $this->assertSame('2026-07-06', $puzzle->puzzle_date->toDateString());
        $this->assertSame(Difficulty::Easy, $puzzle->difficulty);
        $this->assertCount(9, $puzzle->puzzle_config['board']);
        $this->assertNotEmpty($puzzle->puzzle_config['solution_cells']);
        $this->assertCount(count($puzzle->puzzle_config['solution_cells']), $puzzle->question_ids);

        // Every solution cell must point at an empty board cell.
        foreach ($puzzle->puzzle_config['solution_cells'] as $cell) {
            $this->assertNull($puzzle->puzzle_config['board'][$cell]);
        }
    }

    public function test_generation_is_idempotent_per_date(): void
    {
        $generator = app(PuzzleGeneratorService::class);
        $date = Carbon::parse('2026-07-06');

        $first = $generator->generateForDate($date);
        $second = $generator->generateForDate($date);

        $this->assertTrue($first->is($second));
        $this->assertSame(1, DailyPuzzle::count());
    }

    public function test_difficulty_ramps_through_the_week(): void
    {
        $generator = app(PuzzleGeneratorService::class);

        $this->assertSame(Difficulty::Easy, $generator->difficultyForDate(Carbon::parse('2026-07-06')));   // Mon
        $this->assertSame(Difficulty::Easy, $generator->difficultyForDate(Carbon::parse('2026-07-07')));   // Tue
        $this->assertSame(Difficulty::Medium, $generator->difficultyForDate(Carbon::parse('2026-07-08'))); // Wed
        $this->assertSame(Difficulty::Medium, $generator->difficultyForDate(Carbon::parse('2026-07-09'))); // Thu
        $this->assertSame(Difficulty::Hard, $generator->difficultyForDate(Carbon::parse('2026-07-10')));   // Fri
        $this->assertSame(Difficulty::Hard, $generator->difficultyForDate(Carbon::parse('2026-07-12')));   // Sun
    }

    public function test_falls_back_to_other_categories_when_pool_is_thin(): void
    {
        // Add an empty category the date-rotation might land on; the
        // generator must widen the question filter instead of failing.
        Category::factory()->count(3)->create();

        $puzzle = app(PuzzleGeneratorService::class)->generateForDate(Carbon::parse('2026-07-10'));

        $this->assertCount(count($puzzle->puzzle_config['solution_cells']), $puzzle->question_ids);
    }

    public function test_artisan_command_generates_multiple_days(): void
    {
        $this->artisan('puzzle:generate 2026-07-06 --days=3')->assertSuccessful();

        $this->assertSame(3, DailyPuzzle::count());
        $this->assertNotNull(DailyPuzzle::whereDate('puzzle_date', '2026-07-08')->first());
    }
}
