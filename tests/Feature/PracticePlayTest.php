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

    public function test_picker_switches_to_connect_four_and_deals_a_fresh_match(): void
    {
        $this->actingAs(User::factory()->create());

        $component = Livewire::test(Play::class);
        $firstId = $component->get('matchId');

        $component->call('setGameType', 'connect_four')
            ->assertSet('gameType', 'connect_four');

        $match = GameMatch::find($component->get('matchId'));
        $this->assertNotSame($firstId, $match->id);
        $this->assertSame('connect_four', $match->game_type);
        $this->assertCount(6, $match->board_state);
        $this->assertCount(7, $match->board_state[0]);
    }

    public function test_unknown_game_types_are_ignored(): void
    {
        $this->actingAs(User::factory()->create());

        $component = Livewire::test(Play::class);
        $firstId = $component->get('matchId');

        $component->call('setGameType', 'chess')
            ->assertSet('gameType', 'tic_tac_toe');

        $this->assertSame($firstId, $component->get('matchId'));
    }

    public function test_connect_four_correct_answer_drops_a_disc_to_the_bottom(): void
    {
        $this->actingAs(User::factory()->create());

        $component = Livewire::test(Play::class)->call('setGameType', 'connect_four');
        $match = GameMatch::with('currentQuestion.answers')->find($component->get('matchId'));
        $correct = $match->currentQuestion->correctAnswer;

        $component->call('selectCell', '3')
            ->assertSet('selectedPosition', '3')
            ->call('answer', $correct->id)
            ->assertSet('feedback', 'correct');

        $fresh = $match->fresh();
        $this->assertSame('X', $fresh->board_state[5][3]); // bottom of column 3
        $this->assertSame('', $fresh->board_state[4][3]);
    }

    public function test_connect_four_rejects_tic_tac_toe_style_positions(): void
    {
        $this->actingAs(User::factory()->create());

        $component = Livewire::test(Play::class)->call('setGameType', 'connect_four');

        $component->call('selectCell', '1,1')
            ->assertSet('selectedPosition', null);
    }

    public function test_connect_four_wrong_answer_keeps_the_selected_column(): void
    {
        $this->actingAs(User::factory()->create());

        $component = Livewire::test(Play::class)->call('setGameType', 'connect_four');
        $match = GameMatch::with('currentQuestion.answers')->find($component->get('matchId'));
        $wrong = $match->currentQuestion->answers->firstWhere('is_correct', false);

        $component->call('selectCell', '2')
            ->call('answer', $wrong->id)
            ->assertSet('feedback', 'wrong')
            ->assertSet('selectedPosition', '2'); // column retained for retry

        $this->assertNotContains('X', collect($match->fresh()->board_state)->flatten()->all());
    }

    public function test_picker_switches_to_memory_match_and_deals_16_cards(): void
    {
        $this->actingAs(User::factory()->create());

        $component = Livewire::test(Play::class)->call('setGameType', 'memory_match');

        $match = GameMatch::find($component->get('matchId'));
        $this->assertSame('memory_match', $match->game_type);
        $this->assertCount(16, $match->board_state['cards']);
    }

    public function test_memory_match_correct_answer_claims_a_matching_pair(): void
    {
        $this->actingAs(User::factory()->create());

        $component = Livewire::test(Play::class)->call('setGameType', 'memory_match');
        $match = GameMatch::with('currentQuestion.answers')->find($component->get('matchId'));
        $correct = $match->currentQuestion->correctAnswer;

        // Find a real pair in the shuffled layout.
        $byValue = [];
        foreach ($match->board_state['cards'] as $i => $value) {
            $byValue[$value][] = $i;
        }
        [$a, $b] = array_values($byValue)[0];

        $component->call('selectCell', "{$a}|{$b}")
            ->assertSet('selectedPosition', "{$a}|{$b}")
            ->call('answer', $correct->id)
            ->assertSet('feedback', 'correct');

        $fresh = $match->fresh();
        $this->assertSame('X', $fresh->board_state['matched'][$a]);
        $this->assertSame(1, $fresh->board_state['scores']['X']);
        $this->assertTrue($fresh->board_state['last_flip']['matched']);
    }

    public function test_memory_match_miss_reveals_without_claiming(): void
    {
        $this->actingAs(User::factory()->create());

        $component = Livewire::test(Play::class)->call('setGameType', 'memory_match');
        $match = GameMatch::with('currentQuestion.answers')->find($component->get('matchId'));
        $correct = $match->currentQuestion->correctAnswer;

        // Two cards with different values.
        $cards = $match->board_state['cards'];
        $a = 0;
        $b = collect($cards)->search(fn ($value, $i) => $i > 0 && $value !== $cards[0]);

        $component->call('selectCell', "{$a}|{$b}")
            ->call('answer', $correct->id)
            ->assertSet('feedback', 'correct'); // quiz answer right, flip just missed

        $fresh = $match->fresh();
        $this->assertSame([], $fresh->board_state['matched']);
        $this->assertFalse($fresh->board_state['last_flip']['matched']);
        $this->assertEqualsCanonicalizing([$a, $b], $fresh->board_state['revealed']);
    }

    public function test_memory_match_wrong_answer_keeps_the_selected_pair(): void
    {
        $this->actingAs(User::factory()->create());

        $component = Livewire::test(Play::class)->call('setGameType', 'memory_match');
        $match = GameMatch::with('currentQuestion.answers')->find($component->get('matchId'));
        $wrong = $match->currentQuestion->answers->firstWhere('is_correct', false);

        $component->call('selectCell', '0|1')
            ->call('answer', $wrong->id)
            ->assertSet('feedback', 'wrong')
            ->assertSet('selectedPosition', '0|1'); // pair retained for retry

        $this->assertSame([], $match->fresh()->board_state['revealed']);
    }

    public function test_timeout_forfeits_the_match_to_the_ai(): void
    {
        $this->actingAs($user = User::factory()->create());

        $component = Livewire::test(Play::class);
        $component->call('timeout');

        $match = GameMatch::find($component->get('matchId'));
        $this->assertSame(MatchStatus::Abandoned, $match->status);
        $this->assertSame('player2_win', $match->result); // AI (player2) wins
        $this->assertSame(1, $user->fresh()->losses);
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
