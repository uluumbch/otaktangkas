<?php

namespace App\Services\GameEngine\Games;

use App\Contracts\GameInterface;
use App\Models\GameMatch;
use App\Models\User;

/**
 * Connect Four (Empat Sejajar): drop a disc into a column, first to line
 * up four (horizontally, vertically, or diagonally) wins.
 *
 * Board: board[row][col], 6 rows x 7 columns, row 0 at the top. A move's
 * position is the column index as a string ("0".."6"); the disc falls to
 * the lowest empty row of that column.
 */
class ConnectFourGame implements GameInterface
{
    public const ROWS = 6;

    public const COLS = 7;

    public function initialize(GameMatch $match): array
    {
        return array_fill(0, self::ROWS, array_fill(0, self::COLS, ''));
    }

    public function makeMove(GameMatch $match, User $user, string $position, bool $isCorrectAnswer): array
    {
        $board = $match->board_state ?? $this->initialize($match);

        if (! $isCorrectAnswer) {
            return [
                'success' => false,
                'board' => $board,
                'message' => 'Jawaban salah, tidak ada langkah dibuat.',
            ];
        }

        $col = $this->parseColumn($position);

        if ($col === null) {
            return ['success' => false, 'board' => $board, 'message' => 'Kolom tidak valid.'];
        }

        $row = $this->dropRow($board, $col);

        if ($row === null) {
            return ['success' => false, 'board' => $board, 'message' => 'Kolom sudah penuh.'];
        }

        $symbol = $user->id === $match->player1_id ? $match->player1_symbol : $match->player2_symbol;
        $board[$row][$col] = $symbol;

        return [
            'success' => true,
            'board' => $board,
            'symbol' => $symbol,
            'position' => $position,
        ];
    }

    public function checkGameOver(GameMatch $match): ?string
    {
        $board = $match->board_state ?? $this->initialize($match);

        if ($this->checkWin($board, $match->player1_symbol)) {
            return 'player1_win';
        }

        if ($this->checkWin($board, $match->player2_symbol)) {
            return 'player2_win';
        }

        // Any open column means the game continues.
        for ($col = 0; $col < self::COLS; $col++) {
            if ($board[0][$col] === '') {
                return null;
            }
        }

        return 'draw';
    }

    public function getValidMoves(GameMatch $match): array
    {
        $board = $match->board_state ?? $this->initialize($match);
        $moves = [];

        for ($col = 0; $col < self::COLS; $col++) {
            if ($board[0][$col] === '') {
                $moves[] = (string) $col;
            }
        }

        return $moves;
    }

    public function getAIMove(GameMatch $match, string $difficulty = 'medium'): ?string
    {
        $validMoves = $this->getValidMoves($match);

        return $validMoves === [] ? null : $validMoves[array_rand($validMoves)];
    }

    public function getGameState(GameMatch $match): array
    {
        return [
            'board' => $match->board_state,
            'status' => $match->status,
            'current_turn' => $match->current_turn_user_id,
            'result' => $match->result,
            'winner_id' => $match->winner_id,
            'valid_moves' => $this->getValidMoves($match),
        ];
    }

    /**
     * The row a disc dropped into this column lands on, or null when full.
     */
    protected function dropRow(array $board, int $col): ?int
    {
        for ($row = self::ROWS - 1; $row >= 0; $row--) {
            if (($board[$row][$col] ?? null) === '') {
                return $row;
            }
        }

        return null;
    }

    protected function checkWin(array $board, string $symbol): bool
    {
        if ($symbol === '') {
            return false;
        }

        // Direction vectors: right, down, down-right, down-left.
        $directions = [[0, 1], [1, 0], [1, 1], [1, -1]];

        for ($row = 0; $row < self::ROWS; $row++) {
            for ($col = 0; $col < self::COLS; $col++) {
                if (($board[$row][$col] ?? '') !== $symbol) {
                    continue;
                }

                foreach ($directions as [$dr, $dc]) {
                    $count = 1;

                    while ($count < 4) {
                        $r = $row + $dr * $count;
                        $c = $col + $dc * $count;

                        if ($r < 0 || $r >= self::ROWS || $c < 0 || $c >= self::COLS
                            || ($board[$r][$c] ?? '') !== $symbol) {
                            break;
                        }

                        $count++;
                    }

                    if ($count === 4) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Parse a column position ("0".."6"), or null when out of range.
     */
    protected function parseColumn(string $position): ?int
    {
        if (! is_numeric($position)) {
            return null;
        }

        $col = (int) $position;

        return ($col >= 0 && $col < self::COLS) ? $col : null;
    }
}
