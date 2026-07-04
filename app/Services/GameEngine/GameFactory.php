<?php

namespace App\Services\GameEngine;

use App\Contracts\GameInterface;
use App\Services\GameEngine\Games\TicTacToeGame;
use InvalidArgumentException;

class GameFactory
{
    public static function create(string $gameType): GameInterface
    {
        return match ($gameType) {
            'tic_tac_toe' => new TicTacToeGame(),
            default => throw new InvalidArgumentException("Game type {$gameType} not supported"),
        };
    }
}
