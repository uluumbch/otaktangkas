<?php

namespace App\Services\GameEngine;

use App\Contracts\GameInterface;
use App\Services\GameEngine\Games\ConnectFourGame;
use App\Services\GameEngine\Games\MemoryMatchGame;
use App\Services\GameEngine\Games\TicTacToeGame;
use InvalidArgumentException;

class GameFactory
{
    public static function create(string $gameType): GameInterface
    {
        return match ($gameType) {
            'tic_tac_toe' => new TicTacToeGame(),
            'connect_four' => new ConnectFourGame(),
            'memory_match' => new MemoryMatchGame(),
            default => throw new InvalidArgumentException("Game type {$gameType} not supported"),
        };
    }
}
