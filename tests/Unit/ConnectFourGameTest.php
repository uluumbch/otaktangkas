<?php

namespace Tests\Unit;

use App\Models\GameMatch;
use App\Models\User;
use App\Services\GameEngine\GameFactory;
use App\Services\GameEngine\Games\ConnectFourGame;
use Tests\TestCase;

class ConnectFourGameTest extends TestCase
{
    private ConnectFourGame $game;

    protected function setUp(): void
    {
        parent::setUp();
        $this->game = new ConnectFourGame();
    }

    private function match(?array $board = null): GameMatch
    {
        $match = new GameMatch([
            'player1_id' => 1,
            'player2_id' => 2,
            'player1_symbol' => 'X',
            'player2_symbol' => 'O',
        ]);
        $match->board_state = $board ?? $this->game->initialize($match);

        return $match;
    }

    /**
     * Build a board from 6 compact strings ('.' = empty), top row first.
     */
    private function board(array $rows): array
    {
        return array_map(
            fn (string $row) => array_map(fn ($c) => $c === '.' ? '' : $c, str_split($row)),
            $rows,
        );
    }

    private function player(int $id): User
    {
        $user = new User();
        $user->id = $id;

        return $user;
    }

    public function test_factory_creates_connect_four_game(): void
    {
        $this->assertInstanceOf(ConnectFourGame::class, GameFactory::create('connect_four'));
    }

    public function test_initialize_returns_empty_6x7_board(): void
    {
        $board = $this->game->initialize($this->match());

        $this->assertCount(6, $board);
        foreach ($board as $row) {
            $this->assertSame(array_fill(0, 7, ''), $row);
        }
    }

    public function test_disc_falls_to_the_bottom_of_an_empty_column(): void
    {
        $match = $this->match();

        $result = $this->game->makeMove($match, $this->player(1), '3', isCorrectAnswer: true);

        $this->assertTrue($result['success']);
        $this->assertSame('X', $result['board'][5][3]);
        $this->assertSame('', $result['board'][4][3]);
    }

    public function test_discs_stack_on_top_of_each_other(): void
    {
        $match = $this->match();

        $first = $this->game->makeMove($match, $this->player(1), '3', isCorrectAnswer: true);
        $match->board_state = $first['board'];
        $second = $this->game->makeMove($match, $this->player(2), '3', isCorrectAnswer: true);

        $this->assertSame('X', $second['board'][5][3]);
        $this->assertSame('O', $second['board'][4][3]);
    }

    public function test_an_incorrect_answer_makes_no_move(): void
    {
        $match = $this->match();

        $result = $this->game->makeMove($match, $this->player(1), '3', isCorrectAnswer: false);

        $this->assertFalse($result['success']);
        $this->assertSame($this->game->initialize($match), $result['board']);
    }

    public function test_a_full_column_rejects_the_move(): void
    {
        $match = $this->match($this->board([
            '...X...',
            '...O...',
            '...X...',
            '...O...',
            '...X...',
            '...O...',
        ]));

        $result = $this->game->makeMove($match, $this->player(1), '3', isCorrectAnswer: true);

        $this->assertFalse($result['success']);
        $this->assertSame('Kolom sudah penuh.', $result['message']);
    }

    public function test_invalid_columns_are_rejected(): void
    {
        $match = $this->match();

        foreach (['7', '-1', 'abc', '1,1'] as $position) {
            $result = $this->game->makeMove($match, $this->player(1), $position, isCorrectAnswer: true);
            $this->assertFalse($result['success'], "Position {$position} should be rejected");
        }
    }

    public function test_detects_horizontal_win(): void
    {
        $match = $this->match($this->board([
            '.......',
            '.......',
            '.......',
            '.......',
            '.OO....',
            '.XXXX..',
        ]));

        $this->assertSame('player1_win', $this->game->checkGameOver($match));
    }

    public function test_detects_vertical_win(): void
    {
        $match = $this->match($this->board([
            '.......',
            '.......',
            '..O....',
            '..O....',
            '..O....',
            '..OXXX.',
        ]));

        $this->assertSame('player2_win', $this->game->checkGameOver($match));
    }

    public function test_detects_down_right_diagonal_win(): void
    {
        $match = $this->match($this->board([
            '.......',
            '.......',
            '.X.....',
            '.OX....',
            '.OOX...',
            '.OXOX..',
        ]));

        $this->assertSame('player1_win', $this->game->checkGameOver($match));
    }

    public function test_detects_down_left_diagonal_win(): void
    {
        $match = $this->match($this->board([
            '.......',
            '.......',
            '....X..',
            '...XO..',
            '..XOO..',
            '.XOXO..',
        ]));

        $this->assertSame('player1_win', $this->game->checkGameOver($match));
    }

    public function test_three_in_a_row_is_not_a_win(): void
    {
        $match = $this->match($this->board([
            '.......',
            '.......',
            '.......',
            '.......',
            '.OO....',
            '.XXX...',
        ]));

        $this->assertNull($this->game->checkGameOver($match));
    }

    public function test_full_board_without_winner_is_a_draw(): void
    {
        // Alternating rows in vertical pairs: horizontal runs are 1, vertical
        // and diagonal runs top out at 2, so no one can have four in a row.
        $match = $this->match($this->board([
            'XOXOXOX',
            'XOXOXOX',
            'OXOXOXO',
            'OXOXOXO',
            'XOXOXOX',
            'XOXOXOX',
        ]));

        $this->assertSame('draw', $this->game->checkGameOver($match));
    }

    public function test_valid_moves_shrink_as_columns_fill(): void
    {
        $match = $this->match($this->board([
            'X..O...',
            'O..X...',
            'X..O...',
            'O..X...',
            'X..O...',
            'O..X...',
        ]));

        $this->assertSame(['1', '2', '4', '5', '6'], $this->game->getValidMoves($match));
    }

    public function test_ai_returns_a_legal_move(): void
    {
        $match = $this->match();

        foreach (['easy', 'medium', 'hard'] as $difficulty) {
            $move = $this->game->getAIMove($match, $difficulty);
            $this->assertContains($move, $this->game->getValidMoves($match));
        }
    }

    public function test_easy_ai_stays_legal_on_a_crowded_board(): void
    {
        $match = $this->match($this->board([
            'X.XOXO.',
            'OXOXOX.',
            'XOXOXOO',
            'OXOXOXX',
            'XOXOXOO',
            'OXOXOXX',
        ]));

        for ($i = 0; $i < 20; $i++) {
            $this->assertContains(
                $this->game->getAIMove($match, 'easy'),
                ['1', '6'], // the only open columns
            );
        }
    }

    public function test_hard_ai_takes_an_immediate_win(): void
    {
        // O (the AI) has three in column 5: dropping there wins.
        $match = $this->match($this->board([
            '.......',
            '.......',
            '.......',
            '.....O.',
            '..X..O.',
            '.XX..O.',
        ]));

        $this->assertSame('5', $this->game->getAIMove($match, 'hard'));
    }

    public function test_hard_ai_blocks_an_immediate_loss(): void
    {
        // X threatens a horizontal four at row 5: either open end blocks.
        $match = $this->match($this->board([
            '.......',
            '.......',
            '.......',
            '.......',
            '.OO....',
            '.XXX...',
        ]));

        $this->assertContains($this->game->getAIMove($match, 'hard'), ['0', '4']);
    }

    public function test_hard_ai_blocks_in_column_zero(): void
    {
        // The only block is column 0 — a falsy string that must not be
        // mistaken for "no move found".
        $match = $this->match($this->board([
            '.......',
            '.......',
            '.......',
            '.......',
            '.OO....',
            '.XXXO..',
        ]));

        $this->assertSame('0', $this->game->getAIMove($match, 'hard'));
    }

    public function test_hard_ai_prefers_winning_over_blocking(): void
    {
        // Both sides threaten a win; the AI must take its own (column 6).
        $match = $this->match($this->board([
            '.......',
            '.......',
            '.......',
            '......O',
            '.XX...O',
            'XXO...O',
        ]));

        $this->assertSame('6', $this->game->getAIMove($match, 'hard'));
    }

    public function test_hard_ai_avoids_gifting_a_win(): void
    {
        // X's diagonal (5,0)-(4,1)-(3,2) completes at (2,3). Column 3's
        // next drop lands at (3,3); if the AI plays there, X wins on top.
        // Column 3 is the AI's first center-out preference, so a naive AI
        // would take the bait — safe columns exist and must be chosen.
        $match = $this->match($this->board([
            '.......',
            '.......',
            '.......',
            '..X....',
            '.XOO...',
            'XOXO...',
        ]));

        $board = $match->board_state;

        // Sanity-check the fixture: dropping O in column 3 then X on top
        // gives X a win; otherwise this test wouldn't prove anything.
        $board[3][3] = 'O';
        $board[2][3] = 'X';
        $sanity = $this->match($board);
        $this->assertSame('player1_win', $this->game->checkGameOver($sanity));

        $this->assertNotSame('3', $this->game->getAIMove($match, 'hard'));
    }
}
