<?php

namespace App\Services\GameEngine\Games;

use App\Contracts\GameInterface;
use App\Models\GameMatch;
use App\Models\User;

class TicTacToeGame implements GameInterface
{
    public function initialize(GameMatch $match): array
    {
        return [
            ['', '', ''],
            ['', '', ''],
            ['', '', ''],
        ];
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

        [$row, $col] = $this->parsePosition($position);

        if ($row === null || $col === null) {
            return ['success' => false, 'board' => $board, 'message' => 'Posisi tidak valid.'];
        }

        if ($board[$row][$col] !== '') {
            return ['success' => false, 'board' => $board, 'message' => 'Posisi sudah terisi.'];
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

        foreach ($board as $rowCells) {
            foreach ($rowCells as $cell) {
                if ($cell === '') {
                    return null; // board not full, game continues
                }
            }
        }

        return 'draw';
    }

    public function getValidMoves(GameMatch $match): array
    {
        $board = $match->board_state ?? $this->initialize($match);
        $validMoves = [];

        for ($row = 0; $row < 3; $row++) {
            for ($col = 0; $col < 3; $col++) {
                if ($board[$row][$col] === '') {
                    $validMoves[] = "{$row},{$col}";
                }
            }
        }

        return $validMoves;
    }

    public function getAIMove(GameMatch $match, string $difficulty = 'medium'): ?string
    {
        return match ($difficulty) {
            'easy' => $this->getRandomMove($match),
            'hard' => $this->getOptimalMove($match),
            default => $this->getMediumMove($match),
        };
    }

    protected function getRandomMove(GameMatch $match): ?string
    {
        $validMoves = $this->getValidMoves($match);

        return $validMoves === [] ? null : $validMoves[array_rand($validMoves)];
    }

    protected function getMediumMove(GameMatch $match): ?string
    {
        // 70% optimal, 30% random.
        return random_int(1, 100) <= 70
            ? $this->getOptimalMove($match)
            : $this->getRandomMove($match);
    }

    protected function getOptimalMove(GameMatch $match): ?string
    {
        $board = $match->board_state ?? $this->initialize($match);
        $aiSymbol = $match->player2_symbol;
        $playerSymbol = $match->player1_symbol;

        // Win if possible, otherwise block the opponent.
        if ($winningMove = $this->findWinningMove($board, $aiSymbol)) {
            return $winningMove;
        }

        if ($blockingMove = $this->findWinningMove($board, $playerSymbol)) {
            return $blockingMove;
        }

        // Prefer center, then corners, then anything.
        if ($board[1][1] === '') {
            return '1,1';
        }

        foreach (['0,0', '0,2', '2,0', '2,2'] as $corner) {
            [$row, $col] = $this->parsePosition($corner);
            if ($board[$row][$col] === '') {
                return $corner;
            }
        }

        return $this->getRandomMove($match);
    }

    protected function findWinningMove(array $board, string $symbol): ?string
    {
        for ($row = 0; $row < 3; $row++) {
            for ($col = 0; $col < 3; $col++) {
                if ($board[$row][$col] === '') {
                    $testBoard = $board;
                    $testBoard[$row][$col] = $symbol;

                    if ($this->checkWin($testBoard, $symbol)) {
                        return "{$row},{$col}";
                    }
                }
            }
        }

        return null;
    }

    protected function checkWin(array $board, string $symbol): bool
    {
        if ($symbol === '') {
            return false;
        }

        for ($i = 0; $i < 3; $i++) {
            // Rows and columns.
            if ($board[$i][0] === $symbol && $board[$i][1] === $symbol && $board[$i][2] === $symbol) {
                return true;
            }
            if ($board[0][$i] === $symbol && $board[1][$i] === $symbol && $board[2][$i] === $symbol) {
                return true;
            }
        }

        // Diagonals.
        if ($board[0][0] === $symbol && $board[1][1] === $symbol && $board[2][2] === $symbol) {
            return true;
        }

        if ($board[0][2] === $symbol && $board[1][1] === $symbol && $board[2][0] === $symbol) {
            return true;
        }

        return false;
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
     * Parse a "row,col" string into [int, int] or [null, null] if out of range.
     *
     * @return array{0: int|null, 1: int|null}
     */
    protected function parsePosition(string $position): array
    {
        $parts = explode(',', $position);

        if (count($parts) !== 2 || ! is_numeric($parts[0]) || ! is_numeric($parts[1])) {
            return [null, null];
        }

        $row = (int) $parts[0];
        $col = (int) $parts[1];

        if ($row < 0 || $row > 2 || $col < 0 || $col > 2) {
            return [null, null];
        }

        return [$row, $col];
    }
}
