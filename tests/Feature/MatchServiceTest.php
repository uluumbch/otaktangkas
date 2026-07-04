<?php

namespace Tests\Feature;

use App\Enums\MatchStatus;
use App\Events\MatchEnded;
use App\Models\GameMatch;
use App\Models\Question;
use App\Models\User;
use App\Services\MatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class MatchServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): MatchService
    {
        return app(MatchService::class);
    }

    private function seedQuestions(int $count = 15): void
    {
        Question::factory()->count($count)->withAnswers()->create(['language' => 'id']);
    }

    public function test_creating_a_practice_match_starts_in_progress_against_the_ai(): void
    {
        $this->seedQuestions();
        $player = User::factory()->create();

        $match = $this->service()->createMatch($player, 'practice');

        $this->assertSame(MatchStatus::InProgress, $match->status);
        $this->assertNotNull($match->player2_id);
        $this->assertTrue($match->player2->is_guest, 'AI opponent should be a guest user');
        $this->assertSame($player->id, $match->current_turn_user_id);
        $this->assertNotNull($match->current_question_id);
        $this->assertSame([['', '', ''], ['', '', ''], ['', '', '']], $match->board_state);
    }

    public function test_a_correct_answer_places_a_symbol_and_awards_rewards(): void
    {
        $this->seedQuestions();
        $player = User::factory()->create(['xp' => 0, 'coins' => 100]);

        $match = $this->service()->createMatch($player, 'practice');
        $correct = $match->currentQuestion->correctAnswer;
        $xpReward = $match->currentQuestion->xp_reward;

        $result = $this->service()->processAnswer($match, $player, $correct->id, '0,0');

        $this->assertTrue($result['success']);
        $this->assertTrue($result['is_correct']);
        $this->assertSame('X', $match->fresh()->board_state[0][0]);
        $this->assertSame($xpReward, $player->fresh()->xp);
        $this->assertSame(100 + $match->currentQuestion->coins_reward, $player->fresh()->coins);
        // Turn passed to the AI opponent.
        $this->assertSame($match->player2_id, $match->fresh()->current_turn_user_id);
    }

    public function test_an_incorrect_answer_records_a_move_but_does_not_change_the_board(): void
    {
        $this->seedQuestions();
        $player = User::factory()->create(['xp' => 0]);

        $match = $this->service()->createMatch($player, 'practice');
        $wrong = $match->currentQuestion->answers()->where('is_correct', false)->first();

        $result = $this->service()->processAnswer($match, $player, $wrong->id, '0,0');

        $this->assertFalse($result['success']);
        $this->assertFalse($result['is_correct']);
        $this->assertSame('', $match->fresh()->board_state[0][0]);
        $this->assertSame(0, $player->fresh()->xp);
        $this->assertDatabaseHas('match_moves', ['match_id' => $match->id, 'is_correct' => false]);
    }

    public function test_it_is_not_the_players_turn_is_rejected(): void
    {
        $this->seedQuestions();
        $player = User::factory()->create();
        $match = $this->service()->createMatch($player, 'practice');

        // Force it to be the AI's turn.
        $match->update(['current_turn_user_id' => $match->player2_id]);
        $answer = $match->currentQuestion->correctAnswer;

        $result = $this->service()->processAnswer($match, $player, $answer->id, '0,0');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('giliran', mb_strtolower($result['message']));
    }

    public function test_winning_completes_the_match_and_updates_winner_stats(): void
    {
        Event::fake([MatchEnded::class]);
        $this->seedQuestions();
        $player = User::factory()->create();

        $match = $this->service()->createMatch($player, 'practice');
        // X about to win the top row at 0,2.
        $match->board_state = [['X', 'X', ''], ['O', 'O', ''], ['', '', '']];
        $match->current_turn_user_id = $player->id;
        $match->save();

        $correct = $match->currentQuestion->correctAnswer;
        $result = $this->service()->processAnswer($match, $player, $correct->id, '0,2');

        $fresh = $match->fresh();
        $this->assertTrue($result['success']);
        $this->assertSame(MatchStatus::Completed, $fresh->status);
        $this->assertSame('player1_win', $fresh->result);
        $this->assertSame($player->id, $fresh->winner_id);
        $this->assertSame(1, $player->fresh()->wins);
        $this->assertSame(1, $player->fresh()->total_matches);
        $this->assertSame(1, $player->fresh()->win_streak);
        Event::assertDispatched(MatchEnded::class);
    }

    public function test_ai_makes_a_valid_move(): void
    {
        $this->seedQuestions();
        $player = User::factory()->create();

        $match = $this->service()->createMatch($player, 'practice');
        // Human moves first.
        $correct = $match->currentQuestion->correctAnswer;
        $this->service()->processAnswer($match, $player, $correct->id, '1,1');

        $result = $this->service()->processAIMove($match->fresh());

        $this->assertTrue($result['success']);
        // The AI's symbol (O) now appears somewhere on the board.
        $flat = collect($match->fresh()->board_state)->flatten();
        $this->assertContains('O', $flat->all());
    }

    public function test_joining_a_waiting_match_starts_it(): void
    {
        $this->seedQuestions();
        $p1 = User::factory()->create();
        $p2 = User::factory()->create();

        $match = $this->service()->createMatch($p1, 'quick_play');
        $this->assertSame(MatchStatus::Waiting, $match->status);

        $joined = $this->service()->joinMatch($match, $p2);

        $this->assertTrue($joined);
        $this->assertSame(MatchStatus::InProgress, $match->fresh()->status);
        $this->assertSame($p2->id, $match->fresh()->player2_id);
        $this->assertNotNull($match->fresh()->current_question_id);
    }

    public function test_find_quick_play_match_returns_a_waiting_opponent(): void
    {
        $p1 = User::factory()->create(['level' => 5]);
        $seeker = User::factory()->create(['level' => 6]);

        $waiting = $this->service()->createMatch($p1, 'quick_play');

        $found = $this->service()->findQuickPlayMatch($seeker);

        $this->assertNotNull($found);
        $this->assertTrue($found->is($waiting));
    }

    public function test_abandoning_awards_the_opponent(): void
    {
        $this->seedQuestions();
        $p1 = User::factory()->create();
        $p2 = User::factory()->create();

        $match = $this->service()->createMatch($p1, 'quick_play');
        $this->service()->joinMatch($match, $p2);

        $this->service()->abandonMatch($match->fresh(), $p1);

        $fresh = $match->fresh();
        $this->assertSame(MatchStatus::Abandoned, $fresh->status);
        $this->assertSame($p2->id, $fresh->winner_id);
        $this->assertSame(1, $p2->fresh()->wins);
        $this->assertSame(1, $p1->fresh()->losses);
    }
}
