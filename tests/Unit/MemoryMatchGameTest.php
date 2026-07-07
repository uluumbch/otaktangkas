<?php

namespace Tests\Unit;

use App\Models\GameMatch;
use App\Models\User;
use App\Services\GameEngine\GameFactory;
use App\Services\GameEngine\Games\MemoryMatchGame;
use Tests\TestCase;

class MemoryMatchGameTest extends TestCase
{
    private MemoryMatchGame $game;

    protected function setUp(): void
    {
        parent::setUp();
        $this->game = new MemoryMatchGame();
    }

    private function match(?array $state = null): GameMatch
    {
        $match = new GameMatch([
            'player1_id' => 1,
            'player2_id' => 2,
            'player1_symbol' => 'X',
            'player2_symbol' => 'O',
        ]);
        $match->board_state = $state ?? $this->game->initialize($match);

        return $match;
    }

    /**
     * A deterministic 16-card layout: pairs sit at (2i, 2i+1).
     */
    private function orderedState(array $overrides = []): array
    {
        $cards = [];
        foreach (MemoryMatchGame::PAIRS as $value) {
            $cards[] = $value;
            $cards[] = $value;
        }

        return array_merge([
            'cards' => $cards,
            'matched' => [],
            'revealed' => [],
            'scores' => ['X' => 0, 'O' => 0],
            'last_flip' => null,
        ], $overrides);
    }

    private function player(int $id): User
    {
        $user = new User();
        $user->id = $id;

        return $user;
    }

    public function test_factory_creates_memory_match_game(): void
    {
        $this->assertInstanceOf(MemoryMatchGame::class, GameFactory::create('memory_match'));
    }

    public function test_initialize_deals_eight_shuffled_pairs(): void
    {
        $state = $this->game->initialize($this->match($this->orderedState()));

        $this->assertCount(16, $state['cards']);
        $counts = array_count_values($state['cards']);
        $this->assertCount(8, $counts);
        foreach ($counts as $count) {
            $this->assertSame(2, $count);
        }
        $this->assertSame([], $state['matched']);
        $this->assertSame([], $state['revealed']);
        $this->assertSame(['X' => 0, 'O' => 0], $state['scores']);
    }

    public function test_a_matching_pair_is_claimed_and_scored(): void
    {
        $match = $this->match($this->orderedState());

        $result = $this->game->makeMove($match, $this->player(1), '0|1', isCorrectAnswer: true);

        $this->assertTrue($result['success']);
        $this->assertSame('X', $result['board']['matched'][0]);
        $this->assertSame('X', $result['board']['matched'][1]);
        $this->assertSame(1, $result['board']['scores']['X']);
        $this->assertTrue($result['board']['last_flip']['matched']);
        $this->assertSame([0, 1], $result['board']['revealed']);
    }

    public function test_a_miss_reveals_both_cards_without_claiming(): void
    {
        $match = $this->match($this->orderedState());

        $result = $this->game->makeMove($match, $this->player(2), '0|2', isCorrectAnswer: true);

        $this->assertTrue($result['success']);
        $this->assertSame([], $result['board']['matched']);
        $this->assertSame(0, $result['board']['scores']['O']);
        $this->assertFalse($result['board']['last_flip']['matched']);
        $this->assertSame('O', $result['board']['last_flip']['by']);
        $this->assertSame([0, 2], $result['board']['revealed']);
    }

    public function test_an_incorrect_answer_makes_no_move(): void
    {
        $match = $this->match($this->orderedState());

        $result = $this->game->makeMove($match, $this->player(1), '0|1', isCorrectAnswer: false);

        $this->assertFalse($result['success']);
        $this->assertSame([], $result['board']['revealed']);
    }

    public function test_claimed_and_malformed_positions_are_rejected(): void
    {
        $match = $this->match($this->orderedState([
            'matched' => [0 => 'X', 1 => 'X'],
            'scores' => ['X' => 1, 'O' => 0],
        ]));

        foreach (['0|2', '1|3', '5|5', '3', '3|x', '2|16', '1,2'] as $position) {
            $result = $this->game->makeMove($match, $this->player(1), $position, isCorrectAnswer: true);
            $this->assertFalse($result['success'], "Position {$position} should be rejected");
        }
    }

    public function test_positions_are_normalized_min_first(): void
    {
        $match = $this->match($this->orderedState());

        $result = $this->game->makeMove($match, $this->player(1), '3|2', isCorrectAnswer: true);

        $this->assertTrue($result['success']);
        $this->assertSame('2|3', $result['position']);
        $this->assertSame(1, $result['board']['scores']['X']); // pair (2,3) matches
    }

    public function test_five_pairs_clinches_the_match_early(): void
    {
        $matched = [];
        foreach (range(0, 9) as $i) {
            $matched[$i] = 'X';
        }
        $match = $this->match($this->orderedState([
            'matched' => $matched,
            'scores' => ['X' => 5, 'O' => 0],
        ]));

        $this->assertSame('player1_win', $this->game->checkGameOver($match));
    }

    public function test_four_four_is_a_draw(): void
    {
        $matched = [];
        foreach (range(0, 15) as $i) {
            $matched[$i] = $i < 8 ? 'X' : 'O';
        }
        $match = $this->match($this->orderedState([
            'matched' => $matched,
            'scores' => ['X' => 4, 'O' => 4],
        ]));

        $this->assertSame('draw', $this->game->checkGameOver($match));
    }

    public function test_game_continues_while_pairs_remain_without_a_clinch(): void
    {
        $match = $this->match($this->orderedState([
            'matched' => [0 => 'X', 1 => 'X', 2 => 'O', 3 => 'O'],
            'scores' => ['X' => 1, 'O' => 1],
        ]));

        $this->assertNull($this->game->checkGameOver($match));
    }

    public function test_valid_moves_shrink_as_pairs_are_claimed(): void
    {
        $fresh = $this->match($this->orderedState());
        $this->assertCount(120, $this->game->getValidMoves($fresh)); // C(16,2)

        $partial = $this->match($this->orderedState([
            'matched' => [0 => 'X', 1 => 'X'],
        ]));
        $moves = $this->game->getValidMoves($partial);
        $this->assertCount(91, $moves); // C(14,2)
        $this->assertNotContains('0|1', $moves);
        $this->assertContains('2|3', $moves);
    }

    public function test_ai_returns_a_legal_move_at_every_difficulty(): void
    {
        $match = $this->match($this->orderedState([
            'matched' => [0 => 'X', 1 => 'X'],
            'scores' => ['X' => 1, 'O' => 0],
        ]));
        $valid = $this->game->getValidMoves($match);

        foreach (['easy', 'medium', 'hard'] as $difficulty) {
            for ($i = 0; $i < 5; $i++) {
                $this->assertContains($this->game->getAIMove($match, $difficulty), $valid);
            }
        }
    }

    public function test_hard_ai_takes_a_known_match(): void
    {
        // Cards 4 and 5 are a pair and both have been revealed.
        $match = $this->match($this->orderedState([
            'revealed' => [4, 5, 8],
        ]));

        $this->assertSame('4|5', $this->game->getAIMove($match, 'hard'));
    }

    public function test_hard_ai_explores_unrevealed_cards_when_no_match_is_known(): void
    {
        // Revealed cards are all singletons: no known match exists.
        $match = $this->match($this->orderedState([
            'revealed' => [0, 2, 4],
        ]));

        for ($i = 0; $i < 10; $i++) {
            [$a, $b] = array_map('intval', explode('|', $this->game->getAIMove($match, 'hard')));
            $this->assertNotContains($a, [0, 2, 4], 'hard AI should prefer unrevealed cards');
            $this->assertNotContains($b, [0, 2, 4], 'hard AI should prefer unrevealed cards');
        }
    }
}
