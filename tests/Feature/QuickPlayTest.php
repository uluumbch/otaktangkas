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
