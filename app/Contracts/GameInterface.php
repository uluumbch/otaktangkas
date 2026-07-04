<?php

namespace App\Contracts;

use App\Models\GameMatch;
use App\Models\User;

interface GameInterface
{
    /**
     * Build the initial board state for a new match.
     *
     * @return array<int, mixed>
     */
    public function initialize(GameMatch $match): array;

    /**
     * Attempt a move. A move only lands when the answer was correct.
     *
     * @return array{success: bool, board: array<int, mixed>, symbol?: string, position?: string, message?: string}
     */
    public function makeMove(GameMatch $match, User $user, string $position, bool $isCorrectAnswer): array;

    /**
     * Return the game result if the match is over, or null if it continues.
     * One of: player1_win, player2_win, draw.
     */
    public function checkGameOver(GameMatch $match): ?string;

    /**
     * List the positions that may still be played.
     *
     * @return array<int, string>
     */
    public function getValidMoves(GameMatch $match): array;

    /**
     * Choose a move for the AI opponent (practice mode).
     */
    public function getAIMove(GameMatch $match, string $difficulty = 'medium'): ?string;

    /**
     * Snapshot of the current game state for the UI/API.
     *
     * @return array<string, mixed>
     */
    public function getGameState(GameMatch $match): array;
}
