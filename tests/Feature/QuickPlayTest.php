<?php

namespace Tests\Feature;

use App\Enums\MatchStatus;
use App\Livewire\Game\Play;
use App\Livewire\QuickPlay\Lobby;
use App\Models\GameMatch;
use App\Models\Question;
use App\Models\User;
use App\Services\MatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QuickPlayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Question::factory()->count(15)->withAnswers()->create(['language' => 'id']);
    }

    public function test_finding_a_match_with_no_opponent_creates_a_waiting_match(): void
    {
        $this->actingAs($user = User::factory()->create());

        Livewire::test(Lobby::class)
            ->call('findMatch')
            ->assertRedirect();

        $match = GameMatch::where('player1_id', $user->id)->first();
        $this->assertNotNull($match);
        $this->assertSame(MatchStatus::Waiting, $match->status);
    }

    public function test_finding_a_match_joins_a_waiting_opponent(): void
    {
        $host = User::factory()->create(['level' => 3]);
        $waiting = app(MatchService::class)->createMatch($host, 'quick_play');

        $this->actingAs($seeker = User::factory()->create(['level' => 3]));

        Livewire::test(Lobby::class)->call('findMatch');

        $fresh = $waiting->fresh();
        $this->assertSame(MatchStatus::InProgress, $fresh->status);
        $this->assertSame($seeker->id, $fresh->player2_id);
    }

    public function test_connect_four_seekers_are_paired_together(): void
    {
        $host = User::factory()->create(['level' => 3]);
        $waiting = app(MatchService::class)->createMatch($host, 'quick_play', ['game_type' => 'connect_four']);

        $this->actingAs(User::factory()->create(['level' => 3]));

        Livewire::test(Lobby::class)
            ->call('setGameType', 'connect_four')
            ->call('findMatch');

        $fresh = $waiting->fresh();
        $this->assertSame(MatchStatus::InProgress, $fresh->status);
        $this->assertSame('connect_four', $fresh->game_type);
    }

    public function test_a_connect_four_seeker_does_not_join_a_tic_tac_toe_match(): void
    {
        $host = User::factory()->create(['level' => 3]);
        $waiting = app(MatchService::class)->createMatch($host, 'quick_play'); // tic_tac_toe

        $this->actingAs($seeker = User::factory()->create(['level' => 3]));

        Livewire::test(Lobby::class)
            ->call('setGameType', 'connect_four')
            ->call('findMatch');

        // The tic-tac-toe match is still waiting; the seeker opened a new
        // connect_four match instead of being seated at the wrong board.
        $this->assertSame(MatchStatus::Waiting, $waiting->fresh()->status);
        $this->assertNull($waiting->fresh()->player2_id);

        $created = GameMatch::where('player1_id', $seeker->id)->first();
        $this->assertSame('connect_four', $created->game_type);
    }

    public function test_connect_four_multiplayer_round_trip(): void
    {
        $host = User::factory()->create();
        $svc = app(MatchService::class);
        $match = $svc->createMatch($host, 'quick_play', ['game_type' => 'connect_four']);
        $guest = User::factory()->create();
        $svc->joinMatch($match, $guest);

        // Host (player1) drops into column 3.
        $this->actingAs($host);
        $correct = $match->fresh()->currentQuestion->correctAnswer;

        Livewire::test(Play::class, ['match' => $match->fresh()])
            ->call('selectCell', '3')
            ->assertSet('selectedPosition', '3')
            ->call('answer', $correct->id)
            ->assertSet('feedback', 'correct');

        $fresh = $match->fresh();
        $this->assertSame('X', $fresh->board_state[5][3]);
        $this->assertSame($guest->id, $fresh->current_turn_user_id);

        // Guest (player2) answers next and stacks on the same column.
        $this->actingAs($guest);
        $correct = $fresh->currentQuestion->correctAnswer;

        Livewire::test(Play::class, ['match' => $fresh])
            ->call('selectCell', '3')
            ->call('answer', $correct->id)
            ->assertSet('feedback', 'correct');

        $fresh = $match->fresh();
        $this->assertSame('O', $fresh->board_state[4][3]); // gravity stacked
        $this->assertSame($host->id, $fresh->current_turn_user_id);
    }

    public function test_a_non_participant_cannot_view_a_match(): void
    {
        $host = User::factory()->create();
        $match = app(MatchService::class)->createMatch($host, 'quick_play');

        $this->actingAs(User::factory()->create()); // an unrelated user

        Livewire::test(Play::class, ['match' => $match])->assertForbidden();
    }

    public function test_a_participant_can_view_a_match(): void
    {
        $host = User::factory()->create();
        $match = app(MatchService::class)->createMatch($host, 'quick_play');

        $this->actingAs($host);

        Livewire::test(Play::class, ['match' => $match])
            ->assertOk()
            ->assertSee('Menunggu lawan');
    }

    public function test_a_player_cannot_move_when_it_is_not_their_turn(): void
    {
        $host = User::factory()->create();
        $svc = app(MatchService::class);
        $match = $svc->createMatch($host, 'quick_play');
        $guest = User::factory()->create();
        $svc->joinMatch($match, $guest);
        // Turn is host's (player1). Guest tries to move.

        $this->actingAs($guest);
        $answer = $match->fresh()->currentQuestion->correctAnswer;

        Livewire::test(Play::class, ['match' => $match->fresh()])
            ->call('selectCell', '0,0')
            ->assertSet('selectedPosition', null) // ignored: not their turn
            ->call('answer', $answer->id);

        $this->assertSame('', $match->fresh()->board_state[0][0]);
    }

    public function test_timeout_forfeits_to_the_opponent(): void
    {
        $host = User::factory()->create();
        $svc = app(MatchService::class);
        $match = $svc->createMatch($host, 'quick_play');
        $guest = User::factory()->create();
        $svc->joinMatch($match, $guest);
        // Host (player1) is on the clock and times out.

        $this->actingAs($host);
        Livewire::test(Play::class, ['match' => $match->fresh()])->call('timeout');

        $fresh = $match->fresh();
        $this->assertSame(MatchStatus::Abandoned, $fresh->status);
        $this->assertSame($guest->id, $fresh->winner_id);
        $this->assertSame(1, $guest->fresh()->wins);
        $this->assertSame(1, $host->fresh()->losses);
    }

    public function test_a_player_can_move_on_their_turn(): void
    {
        $host = User::factory()->create();
        $svc = app(MatchService::class);
        $match = $svc->createMatch($host, 'quick_play');
        $guest = User::factory()->create();
        $svc->joinMatch($match, $guest);

        $this->actingAs($host); // player1's turn
        $correct = $match->fresh()->currentQuestion->correctAnswer;

        Livewire::test(Play::class, ['match' => $match->fresh()])
            ->call('selectCell', '1,1')
            ->assertSet('selectedPosition', '1,1')
            ->call('answer', $correct->id)
            ->assertSet('feedback', 'correct');

        $fresh = $match->fresh();
        $this->assertSame('X', $fresh->board_state[1][1]);
        $this->assertSame($guest->id, $fresh->current_turn_user_id); // passed to opponent
    }
}
