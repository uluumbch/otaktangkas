<?php

namespace Tests\Unit;

use App\Models\GameMatch;
use App\Models\User;
use App\Services\GameEngine\GameFactory;
use App\Services\GameEngine\Games\TicTacToeGame;
use Tests\TestCase;

class TicTacToeGameTest extends TestCase
{
    private function match(array $board, string $p1 = 'X', string $p2 = 'O'): GameMatch
    {
        $match = new GameMatch([
            'player1_id' => 1,
            'player2_id' => 2,
            'player1_symbol' => $p1,
            'player2_symbol' => $p2,
        ]);
        $match->board_state = $board;

        return $match;
    }

    private function player(int $id): User
    {
        $user = new User();
        $user->id = $id;

        return $user;
    }

    public function test_factory_creates_tic_tac_toe_game(): void
    {
        $this->assertInstanceOf(TicTacToeGame::class, GameFactory::create('tic_tac_toe'));
    }

    public function test_factory_rejects_unknown_game_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        GameFactory::create('chess');
    }

    public function test_initialize_returns_empty_3x3_board(): void
    {
        $game = new TicTacToeGame();
        $board = $game->initialize($this->match([]));

        $this->assertCount(3, $board);
        $this->assertSame([['', '', ''], ['', '', ''], ['', '', '']], $board);
    }

    public function test_a_correct_answer_places_the_players_symbol(): void
    {
        $game = new TicTacToeGame();
        $match = $this->match([['', '', ''], ['', '', ''], ['', '', '']]);

        $result = $game->makeMove($match, $this->player(1), '1,1', isCorrectAnswer: true);

        $this->assertTrue($result['success']);
        $this->assertSame('X', $result['board'][1][1]);
        $this->assertSame('X', $result['symbol']);
    }

    public function test_an_incorrect_answer_does_not_place_a_symbol(): void
    {
        $game = new TicTacToeGame();
        $match = $this->match([['', '', ''], ['', '', ''], ['', '', '']]);

        $result = $game->makeMove($match, $this->player(1), '1,1', isCorrectAnswer: false);

        $this->assertFalse($result['success']);
        $this->assertSame('', $result['board'][1][1]);
    }

    public function test_cannot_move_on_an_occupied_cell(): void
    {
        $game = new TicTacToeGame();
        $match = $this->match([['O', '', ''], ['', '', ''], ['', '', '']]);

        $result = $game->makeMove($match, $this->player(1), '0,0', isCorrectAnswer: true);

        $this->assertFalse($result['success']);
    }

    public function test_out_of_range_position_is_rejected(): void
    {
        $game = new TicTacToeGame();
        $match = $this->match([['', '', ''], ['', '', ''], ['', '', '']]);

        $this->assertFalse($game->makeMove($match, $this->player(1), '3,0', true)['success']);
    }

    public function test_detects_row_column_and_diagonal_wins(): void
    {
        $game = new TicTacToeGame();

        $this->assertSame('player1_win', $game->checkGameOver($this->match([
            ['X', 'X', 'X'], ['O', 'O', ''], ['', '', ''],
        ])));

        $this->assertSame('player2_win', $game->checkGameOver($this->match([
            ['O', 'X', 'X'], ['O', 'X', ''], ['O', '', ''],
        ])));

        $this->assertSame('player1_win', $game->checkGameOver($this->match([
            ['X', 'O', 'O'], ['', 'X', ''], ['', '', 'X'],
        ])));
    }

    public function test_detects_draw_and_ongoing(): void
    {
        $game = new TicTacToeGame();

        $this->assertSame('draw', $game->checkGameOver($this->match([
            ['X', 'O', 'X'], ['X', 'O', 'O'], ['O', 'X', 'X'],
        ])));

        $this->assertNull($game->checkGameOver($this->match([
            ['X', '', ''], ['', 'O', ''], ['', '', ''],
        ])));
    }

    public function test_valid_moves_lists_only_empty_cells(): void
    {
        $game = new TicTacToeGame();
        $moves = $game->getValidMoves($this->match([
            ['X', 'O', ''], ['', 'X', ''], ['', '', 'O'],
        ]));

        $this->assertEqualsCanonicalizing(['0,2', '1,0', '1,2', '2,0', '2,1'], $moves);
    }

    public function test_optimal_ai_takes_the_winning_move(): void
    {
        $game = new TicTacToeGame();
        // AI is O; O has two in the top row and can win at 0,2.
        $move = $game->getAIMove($this->match([
            ['O', 'O', ''], ['X', 'X', ''], ['', '', ''],
        ]), 'hard');

        $this->assertSame('0,2', $move);
    }

    public function test_optimal_ai_blocks_the_opponent(): void
    {
        $game = new TicTacToeGame();
        // X (player1) threatens to win at 0,2; AI (O) must block there.
        $move = $game->getAIMove($this->match([
            ['X', 'X', ''], ['O', '', ''], ['', '', ''],
        ]), 'hard');

        $this->assertSame('0,2', $move);
    }

    public function test_easy_ai_returns_a_valid_move(): void
    {
        $game = new TicTacToeGame();
        $match = $this->match([['X', 'O', ''], ['', 'X', ''], ['', '', 'O']]);

        $move = $game->getAIMove($match, 'easy');

        $this->assertContains($move, $game->getValidMoves($match));
    }
}
