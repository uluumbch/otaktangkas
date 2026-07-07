<?php

namespace Tests\Feature;

use App\Livewire\Puzzle\Play;
use App\Models\Answer;
use App\Models\Category;
use App\Models\DailyPuzzle;
use App\Models\Question;
use App\Models\User;
use App\Services\DailyPuzzleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DailyPuzzleUiTest extends TestCase
{
    use RefreshDatabase;

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
    }

    public function test_page_requires_authentication(): void
    {
        $this->get(route('daily-puzzle'))->assertRedirect(route('login'));
    }

    public function test_shows_intro_before_starting(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(Play::class)
            ->assertOk()
            ->assertSee('Puzzle Harian')
            ->assertSee('Mulai Puzzle');

        // Mounting alone must not create an attempt or start the clock.
        $this->assertSame(0, DailyPuzzle::first()->attempts_count);
    }

    public function test_full_playthrough_completes_the_puzzle(): void
    {
        $this->actingAs($user = User::factory()->create(['xp' => 0]));
        $service = app(DailyPuzzleService::class);

        $component = Livewire::test(Play::class)->call('start');

        $puzzle = DailyPuzzle::first();
        $solutionCount = count($puzzle->puzzle_config['solution_cells']);

        for ($i = 0; $i < $solutionCount; $i++) {
            $attempt = $puzzle->attempts()->where('user_id', $user->id)->first();
            $correct = $service->currentQuestion($puzzle, $attempt)
                ->answers->firstWhere('is_correct', true);

            $component->call('answer', $correct->id);
        }

        $component
            ->assertSee('Puzzle Selesai!')
            ->assertSee('Peringkat Hari Ini')
            ->assertSee($user->name); // player appears in today's ranking

        $this->assertSame($puzzle->xp_reward, $user->fresh()->xp);
    }

    public function test_wrong_answer_shows_retry_feedback(): void
    {
        $this->actingAs($user = User::factory()->create());
        $service = app(DailyPuzzleService::class);

        $component = Livewire::test(Play::class)->call('start');

        $puzzle = DailyPuzzle::first();
        $attempt = $puzzle->attempts()->where('user_id', $user->id)->first();
        $wrong = $service->currentQuestion($puzzle, $attempt)
            ->answers->firstWhere('is_correct', false);

        $component->call('answer', $wrong->id)
            ->assertSee('Salah, coba lagi');

        $this->assertFalse($attempt->fresh()->is_completed);
    }

    public function test_expired_attempt_shows_timeout_state(): void
    {
        $this->actingAs($user = User::factory()->create());

        $component = Livewire::test(Play::class)->call('start');

        $puzzle = DailyPuzzle::first();
        $attempt = $puzzle->attempts()->where('user_id', $user->id)->first();
        $attempt->created_at = now()->subSeconds($puzzle->time_limit + 5);
        $attempt->save();

        $component->call('timedOut')
            ->assertSee('Waktu Habis');

        // Answers are rejected once expired.
        $component->call('answer', Answer::first()->id);
        $this->assertFalse($attempt->fresh()->is_completed);
        $this->assertSame(0, $attempt->fresh()->moves_used);
    }
}
