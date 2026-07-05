<?php

namespace Tests\Feature;

use App\Enums\MatchStatus;
use App\Livewire\Practice\Play;
use App\Models\GameMatch;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PracticePlayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Question::factory()->count(15)->withAnswers()->create(['language' => 'id']);
    }

    public function test_mounting_creates_an_in_progress_practice_match(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(Play::class)
            ->assertSet('matchId', fn ($id) => is_int($id) && $id > 0)
            ->assertStatus(200);
    }

    public function test_the_practice_page_renders_for_an_authenticated_user(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('practice'))->assertOk()->assertSee('Mode Latihan');
    }

    public function test_answering_without_selecting_a_cell_shows_an_error(): void
    {
        $this->actingAs(User::factory()->create());

        $component = Livewire::test(Play::class);
        $match = GameMatch::find($component->get('matchId'));
        $answerId = $match->currentQuestion->answers->first()->id;

        $component->call('answer', $answerId)->assertHasErrors('board');
    }

    public function test_selecting_a_cell_and_answering_correctly_places_a_symbol(): void
    {
        $this->actingAs($user = User::factory()->create());

        $component = Livewire::test(Play::class);
        $match = GameMatch::with('currentQuestion.answers')->find($component->get('matchId'));
        $correct = $match->currentQuestion->correctAnswer;

        $component->call('selectCell', '1,1')
            ->assertSet('selectedPosition', '1,1')
            ->call('answer', $correct->id)
            ->assertSet('feedback', 'correct');

        $fresh = $match->fresh();
        // Human 'X' landed; the AI has NOT moved yet (deferred to aiTurn()).
        $this->assertSame('X', $fresh->board_state[1][1]);
        $this->assertSame($fresh->player2_id, $fresh->current_turn_user_id);
        $this->assertNotContains('O', collect($fresh->board_state)->flatten()->all());
    }

    public function test_ai_turn_makes_the_opponent_move(): void
    {
        $this->actingAs(User::factory()->create());

        $component = Livewire::test(Play::class);
        $match = GameMatch::with('currentQuestion.answers')->find($component->get('matchId'));
        $correct = $match->currentQuestion->correctAnswer;

        $component->call('selectCell', '1,1')->call('answer', $correct->id);
        // Now the AI's turn is pending; the view would call this after a delay.
        $component->call('aiTurn');

        $fresh = $match->fresh();
        $this->assertContains('O', collect($fresh->board_state)->flatten()->all());
        $this->assertSame($fresh->player1_id, $fresh->current_turn_user_id); // back to the player
    }

    public function test_a_wrong_answer_keeps_the_selected_cell_for_a_retry(): void
    {
        $this->actingAs(User::factory()->create());

        $component = Livewire::test(Play::class);
        $match = GameMatch::with('currentQuestion.answers')->find($component->get('matchId'));
        $wrong = $match->currentQuestion->answers->firstWhere('is_correct', false);
        $correct = $match->currentQuestion->correctAnswer;

        $component->call('selectCell', '0,0')
            ->call('answer', $wrong->id)
            ->assertSet('feedback', 'wrong')
            ->assertSet('selectedPosition', '0,0'); // cell retained

        // The same cell can now be answered correctly without re-selecting.
        $component->call('answer', $correct->id)->assertSet('feedback', 'correct');
        $this->assertSame('X', $match->fresh()->board_state[0][0]);
    }

    public function test_new_game_starts_a_fresh_match(): void
    {
        $this->actingAs(User::factory()->create());

        $component = Livewire::test(Play::class);
        $firstId = $component->get('matchId');

        $component->call('newGame');

        $this->assertNotSame($firstId, $component->get('matchId'));
        $this->assertSame(MatchStatus::InProgress, GameMatch::find($component->get('matchId'))->status);
    }
}
