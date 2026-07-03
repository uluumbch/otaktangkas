# 🎮 Phase 2: Game Engine

> **Duration**: 4-5 days  
> **Goal**: Build game engine architecture, implement Tic-Tac-Toe, AI opponent, and real-time game state management

## 📋 Overview

Phase 2 creates the core game engine that powers OtakTangkas. We'll implement a flexible architecture that supports multiple game types, starting with Tic-Tac-Toe.

---

## 🏗️ Game Engine Architecture

### Game Interface

**File**: `app/Contracts/GameInterface.php`

```php
<?php

namespace App\Contracts;

use App\Models\Match;
use App\Models\User;

interface GameInterface
{
    /**
     * Initialize a new game
     */
    public function initialize(Match $match): array;

    /**
     * Make a move
     */
    public function makeMove(Match $match, User $user, string $position, bool $isCorrectAnswer): array;

    /**
     * Check if game is over
     */
    public function checkGameOver(Match $match): ?string;

    /**
     * Get valid moves
     */
    public function getValidMoves(Match $match): array;

    /**
     * Get AI move (for practice mode)
     */
    public function getAIMove(Match $match, string $difficulty = 'medium'): ?string;

    /**
     * Get game state
     */
    public function getGameState(Match $match): array;
}
```

### Game Factory

**File**: `app/Services/GameEngine/GameFactory.php`

```php
<?php

namespace App\Services\GameEngine;

use App\Contracts\GameInterface;
use App\Services\GameEngine\Games\TicTacToeGame;
use InvalidArgumentException;

class GameFactory
{
    public static function create(string $gameType): GameInterface
    {
        return match($gameType) {
            'tic_tac_toe' => new TicTacToeGame(),
            default => throw new InvalidArgumentException("Game type {$gameType} not supported"),
        };
    }
}
```

### Tic-Tac-Toe Game Implementation

**File**: `app/Services/GameEngine/Games/TicTacToeGame.php`

```php
<?php

namespace App/Services/GameEngine\Games;

use App\Contracts\GameInterface;
use App\Models\Match;
use App\Models\User;

class TicTacToeGame implements GameInterface
{
    public function initialize(Match $match): array
    {
        // Create empty 3x3 board
        return [
            ['', '', ''],
            ['', '', ''],
            ['', '', ''],
        ];
    }

    public function makeMove(Match $match, User $user, string $position, bool $isCorrectAnswer): array
    {
        $board = $match->board_state;
        
        // Only allow move if answer is correct
        if (!$isCorrectAnswer) {
            return [
                'success' => false,
                'board' => $board,
                'message' => 'Incorrect answer, no move made',
            ];
        }

        // Parse position (e.g., "0,1" or "1,2")
        [$row, $col] = explode(',', $position);
        
        // Validate position
        if ($row < 0 || $row > 2 || $col < 0 || $col > 2) {
            return [
                'success' => false,
                'board' => $board,
                'message' => 'Invalid position',
            ];
        }

        // Check if position is empty
        if ($board[$row][$col] !== '') {
            return [
                'success' => false,
                'board' => $board,
                'message' => 'Position already occupied',
            ];
        }

        // Determine player symbol
        $symbol = $user->id === $match->player1_id ? $match->player1_symbol : $match->player2_symbol;

        // Make the move
        $board[$row][$col] = $symbol;

        return [
            'success' => true,
            'board' => $board,
            'symbol' => $symbol,
            'position' => $position,
        ];
    }

    public function checkGameOver(Match $match): ?string
    {
        $board = $match->board_state;
        $player1Symbol = $match->player1_symbol;
        $player2Symbol = $match->player2_symbol;

        // Check rows
        for ($row = 0; $row < 3; $row++) {
            if ($board[$row][0] !== '' && 
                $board[$row][0] === $board[$row][1] && 
                $board[$row][1] === $board[$row][2]) {
                return $board[$row][0] === $player1Symbol ? 'player1_win' : 'player2_win';
            }
        }

        // Check columns
        for ($col = 0; $col < 3; $col++) {
            if ($board[0][$col] !== '' && 
                $board[0][$col] === $board[1][$col] && 
                $board[1][$col] === $board[2][$col]) {
                return $board[0][$col] === $player1Symbol ? 'player1_win' : 'player2_win';
            }
        }

        // Check diagonals
        if ($board[0][0] !== '' && 
            $board[0][0] === $board[1][1] && 
            $board[1][1] === $board[2][2]) {
            return $board[0][0] === $player1Symbol ? 'player1_win' : 'player2_win';
        }

        if ($board[0][2] !== '' && 
            $board[0][2] === $board[1][1] && 
            $board[1][1] === $board[2][0]) {
            return $board[0][2] === $player1Symbol ? 'player1_win' : 'player2_win';
        }

        // Check for draw
        $isFull = true;
        foreach ($board as $row) {
            foreach ($row as $cell) {
                if ($cell === '') {
                    $isFull = false;
                    break 2;
                }
            }
        }

        return $isFull ? 'draw' : null;
    }

    public function getValidMoves(Match $match): array
    {
        $board = $match->board_state;
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

    public function getAIMove(Match $match, string $difficulty = 'medium'): ?string
    {
        return match($difficulty) {
            'easy' => $this->getRandomMove($match),
            'medium' => $this->getMediumMove($match),
            'hard' => $this->getOptimalMove($match),
            default => $this->getMediumMove($match),
        };
    }

    protected function getRandomMove(Match $match): ?string
    {
        $validMoves = $this->getValidMoves($match);
        return !empty($validMoves) ? $validMoves[array_rand($validMoves)] : null;
    }

    protected function getMediumMove(Match $match): ?string
    {
        // 70% chance to play optimal move, 30% random
        if (rand(1, 100) <= 70) {
            return $this->getOptimalMove($match);
        }
        return $this->getRandomMove($match);
    }

    protected function getOptimalMove(Match $match): ?string
    {
        $board = $match->board_state;
        $aiSymbol = $match->player2_symbol;
        $playerSymbol = $match->player1_symbol;

        // Check for winning move
        $winningMove = $this->findWinningMove($board, $aiSymbol);
        if ($winningMove) {
            return $winningMove;
        }

        // Block player's winning move
        $blockingMove = $this->findWinningMove($board, $playerSymbol);
        if ($blockingMove) {
            return $blockingMove;
        }

        // Take center if available
        if ($board[1][1] === '') {
            return '1,1';
        }

        // Take corners
        $corners = ['0,0', '0,2', '2,0', '2,2'];
        foreach ($corners as $corner) {
            [$row, $col] = explode(',', $corner);
            if ($board[$row][$col] === '') {
                return $corner;
            }
        }

        // Take any available move
        return $this->getRandomMove($match);
    }

    protected function findWinningMove(array $board, string $symbol): ?string
    {
        // Try each empty position
        for ($row = 0; $row < 3; $row++) {
            for ($col = 0; $col < 3; $col++) {
                if ($board[$row][$col] === '') {
                    // Simulate move
                    $testBoard = $board;
                    $testBoard[$row][$col] = $symbol;
                    
                    // Check if this creates a win
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
        // Check rows
        for ($row = 0; $row < 3; $row++) {
            if ($board[$row][0] === $symbol && 
                $board[$row][1] === $symbol && 
                $board[$row][2] === $symbol) {
                return true;
            }
        }

        // Check columns
        for ($col = 0; $col < 3; $col++) {
            if ($board[0][$col] === $symbol && 
                $board[1][$col] === $symbol && 
                $board[2][$col] === $symbol) {
                return true;
            }
        }

        // Check diagonals
        if ($board[0][0] === $symbol && 
            $board[1][1] === $symbol && 
            $board[2][2] === $symbol) {
            return true;
        }

        if ($board[0][2] === $symbol && 
            $board[1][1] === $symbol && 
            $board[2][0] === $symbol) {
            return true;
        }

        return false;
    }

    public function getGameState(Match $match): array
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
}
```

---

## 🎯 Match Service

**File**: `app/Services/MatchService.php`

```php
<?php

namespace App\Services;

use App\Models\Match;
use App\Models\User;
use App\Models\Question;
use App\Services\GameEngine\GameFactory;
use Illuminate\Support\Facades\DB;

class MatchService
{
    public function __construct(
        protected QuestionService $questionService
    ) {}

    /**
     * Create a new match
     */
    public function createMatch(User $player1, string $mode, array $options = []): Match
    {
        return DB::transaction(function () use ($player1, $mode, $options) {
            $match = Match::create([
                'game_type' => $options['game_type'] ?? 'tic_tac_toe',
                'mode' => $mode,
                'player1_id' => $player1->id,
                'player1_symbol' => 'X',
                'player2_symbol' => 'O',
                'status' => $mode === 'practice' ? 'in_progress' : 'waiting',
                'category_id' => $options['category_id'] ?? null,
                'difficulty' => $options['difficulty'] ?? 'mixed',
                'turn_time_limit' => $options['time_limit'] ?? 30,
            ]);

            // Initialize game board
            $game = GameFactory::create($match->game_type);
            $match->board_state = $game->initialize($match);
            
            if ($mode === 'practice') {
                // Create AI opponent
                $aiOpponent = $this->createAIOpponent();
                $match->player2_id = $aiOpponent->id;
                $match->current_turn_user_id = $player1->id;
                $match->started_at = now();
                $match->turn_started_at = now();
                
                // Get first question
                $question = $this->questionService->getRandomQuestion($match);
                $match->current_question_id = $question->id;
                $match->question_history = [$question->id];
            }

            $match->save();

            return $match;
        });
    }

    /**
     * Join an existing match
     */
    public function joinMatch(Match $match, User $player2): bool
    {
        if ($match->status !== 'waiting' || $match->player2_id) {
            return false;
        }

        return DB::transaction(function () use ($match, $player2) {
            $match->update([
                'player2_id' => $player2->id,
                'status' => 'in_progress',
                'started_at' => now(),
                'current_turn_user_id' => $match->player1_id,
                'turn_started_at' => now(),
            ]);

            // Get first question
            $question = $this->questionService->getRandomQuestion($match);
            $match->update([
                'current_question_id' => $question->id,
                'question_history' => [$question->id],
            ]);

            return true;
        });
    }

    /**
     * Process answer and make move
     */
    public function processAnswer(Match $match, User $user, int $answerId, string $position): array
    {
        if ($match->status !== 'in_progress') {
            return ['success' => false, 'message' => 'Match not in progress'];
        }

        if (!$match->isPlayerTurn($user)) {
            return ['success' => false, 'message' => 'Not your turn'];
        }

        return DB::transaction(function () use ($match, $user, $answerId, $position) {
            $question = $match->currentQuestion;
            $answer = $question->answers()->find($answerId);
            $isCorrect = $answer && $answer->is_correct;

            // Record move
            $move = $match->moves()->create([
                'user_id' => $user->id,
                'question_id' => $question->id,
                'answer_id' => $answerId,
                'move_number' => $match->moves()->count() + 1,
                'position' => $position,
                'is_correct' => $isCorrect,
                'time_taken' => now()->diffInSeconds($match->turn_started_at),
            ]);

            // Update question statistics
            $question->incrementUsage($isCorrect);

            // Process game move
            $game = GameFactory::create($match->game_type);
            $moveResult = $game->makeMove($match, $user, $position, $isCorrect);

            if ($moveResult['success']) {
                $match->board_state = $moveResult['board'];
                
                // Award XP and coins for correct answer
                if ($isCorrect) {
                    $xp = $question->xp_reward;
                    $coins = $question->coins_reward;
                    
                    $user->addXP($xp);
                    $user->addCoins($coins, "Correct answer in match", $match);
                    
                    $move->update([
                        'xp_earned' => $xp,
                        'coins_earned' => $coins,
                    ]);
                }

                // Check if game is over
                $gameResult = $game->checkGameOver($match);
                
                if ($gameResult) {
                    $this->endMatch($match, $gameResult);
                } else {
                    // Switch turns
                    $nextPlayer = $match->getOpponent($user);
                    $match->current_turn_user_id = $nextPlayer->id;
                    $match->turn_started_at = now();

                    // Get next question
                    $nextQuestion = $this->questionService->getRandomQuestion($match);
                    $match->current_question_id = $nextQuestion->id;
                    
                    $questionHistory = $match->question_history ?? [];
                    $questionHistory[] = $nextQuestion->id;
                    $match->question_history = $questionHistory;

                    // If practice mode and AI's turn
                    if ($match->mode === 'practice' && $nextPlayer->is_guest) {
                        // AI will answer after a delay (handled by frontend)
                    }
                }

                $match->save();
            }

            return [
                'success' => $moveResult['success'],
                'is_correct' => $isCorrect,
                'move' => $move,
                'match' => $match->fresh(),
                'game_state' => $game->getGameState($match),
            ];
        });
    }

    /**
     * Handle AI move (for practice mode)
     */
    public function processAIMove(Match $match): array
    {
        $game = GameFactory::create($match->game_type);
        $position = $game->getAIMove($match, 'medium');

        if (!$position) {
            return ['success' => false, 'message' => 'No valid moves'];
        }

        // AI always answers correctly
        $correctAnswer = $match->currentQuestion->correctAnswer();

        return $this->processAnswer($match, $match->player2, $correctAnswer->id, $position);
    }

    /**
     * End match and award rewards
     */
    protected function endMatch(Match $match, string $result): void
    {
        $match->status = 'completed';
        $match->result = $result;
        $match->ended_at = now();

        // Determine winner
        if ($result === 'player1_win') {
            $match->winner_id = $match->player1_id;
            $winner = $match->player1;
            $loser = $match->player2;
        } elseif ($result === 'player2_win') {
            $match->winner_id = $match->player2_id;
            $winner = $match->player2;
            $loser = $match->player1;
        } else {
            // Draw
            $winner = null;
            $loser = null;
        }

        // Award rewards
        $baseXP = 50;
        $baseCoins = 25;

        if ($winner) {
            // Winner rewards
            $winnerXP = (int)($baseXP * 1.5);
            $winnerCoins = (int)($baseCoins * 1.5);
            
            $winner->addXP($winnerXP);
            $winner->addCoins($winnerCoins, "Won match", $match);
            
            // Update winner stats
            $winner->increment('total_matches');
            $winner->increment('wins');
            $winner->increment('win_streak');
            
            if ($winner->win_streak > $winner->best_win_streak) {
                $winner->best_win_streak = $winner->win_streak;
            }
            
            $winner->save();

            // Loser (if not AI)
            if ($loser && !$loser->is_guest) {
                $loser->addXP($baseXP);
                $loser->addCoins($baseCoins, "Completed match", $match);
                $loser->increment('total_matches');
                $loser->increment('losses');
                $loser->win_streak = 0;
                $loser->save();
            }
        } else {
            // Draw - both get base rewards
            foreach ([$match->player1, $match->player2] as $player) {
                if ($player && !$player->is_guest) {
                    $player->addXP($baseXP);
                    $player->addCoins($baseCoins, "Draw match", $match);
                    $player->increment('total_matches');
                    $player->increment('draws');
                    $player->win_streak = 0;
                    $player->save();
                }
            }
        }

        $match->xp_awarded = $baseXP;
        $match->coins_awarded = $baseCoins;
        $match->save();

        // Trigger match end event
        event(new \App\Events\MatchEnded($match));
    }

    /**
     * Create AI opponent for practice mode
     */
    protected function createAIOpponent(): User
    {
        static $aiUser = null;

        if (!$aiUser) {
            $aiUser = User::firstOrCreate(
                ['email' => 'ai@otaktangkas.com'],
                [
                    'name' => 'AI Opponent',
                    'username' => 'ai_opponent',
                    'password' => bcrypt(str()->random(32)),
                    'is_guest' => true,
                    'email_verified_at' => now(),
                ]
            );
        }

        return $aiUser;
    }

    /**
     * Find match for quick play
     */
    public function findQuickPlayMatch(User $user, array $options = []): ?Match
    {
        // Look for waiting matches in similar level range
        $levelRange = 5;
        
        return Match::where('status', 'waiting')
            ->where('mode', 'quick_play')
            ->where('player1_id', '!=', $user->id)
            ->whereHas('player1', function ($query) use ($user, $levelRange) {
                $query->whereBetween('level', [
                    max(1, $user->level - $levelRange),
                    $user->level + $levelRange
                ]);
            })
            ->when($options['category_id'] ?? null, function ($query, $categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->oldest()
            ->first();
    }

    /**
     * Abandon match (timeout or disconnect)
     */
    public function abandonMatch(Match $match, ?User $player = null): void
    {
        $match->update([
            'status' => 'abandoned',
            'result' => 'abandoned',
            'ended_at' => now(),
        ]);

        // If a specific player abandoned, the other wins
        if ($player) {
            $opponent = $match->getOpponent($player);
            if ($opponent && !$opponent->is_guest) {
                $match->winner_id = $opponent->id;
                $match->result = $player->id === $match->player1_id ? 'player2_win' : 'player1_win';
                $match->save();

                // Award win to opponent
                $opponent->addXP(30);
                $opponent->addCoins(15, "Opponent abandoned match", $match);
                $opponent->increment('wins');
                $opponent->increment('total_matches');
                $opponent->save();
            }

            // Penalty for abandoning
            if (!$player->is_guest) {
                $player->increment('losses');
                $player->increment('total_matches');
                $player->win_streak = 0;
                $player->save();
            }
        }
    }
}
```

---

## 🎯 Question Service

**File**: `app/Services/QuestionService.php`

```php
<?php

namespace App\Services;

use App\Models\Question;
use App\Models\Match;
use App\Models\User;

class QuestionService
{
    /**
     * Get random question for match
     */
    public function getRandomQuestion(Match $match): Question
    {
        $query = Question::where('is_active', true)
            ->where('language', app()->getLocale())
            ->whereNotIn('id', $match->question_history ?? []);

        // Filter by category if specified
        if ($match->category_id) {
            $query->where('category_id', $match->category_id);
        }

        // Filter by difficulty
        if ($match->difficulty !== 'mixed') {
            $query->where('difficulty', $match->difficulty);
        }

        // Get appropriate difficulty based on player levels
        if ($match->difficulty === 'mixed') {
            $avgLevel = ($match->player1->level + ($match->player2->level ?? 1)) / 2;
            
            if ($avgLevel < 10) {
                $query->where('difficulty', 'easy');
            } elseif ($avgLevel < 25) {
                $query->where('difficulty', 'medium');
            } else {
                $query->where('difficulty', 'hard');
            }
        }

        return $query->inRandomOrder()->firstOrFail();
    }

    /**
     * Get questions for daily puzzle
     */
    public function getQuestionsForDailyPuzzle(int $count, string $difficulty, ?int $categoryId = null): array
    {
        $query = Question::where('is_active', true)
            ->where('difficulty', $difficulty)
            ->where('language', 'id');

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        return $query->inRandomOrder()
            ->limit($count)
            ->pluck('id')
            ->toArray();
    }
}
```

---

## 📡 Real-time Events

### Match Events

**File**: `app/Events/MatchUpdated.php`

```php
<?php

namespace App\Events;

use App\Models\Match;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MatchUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Match $match
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('match.' . $this->match->id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'match' => $this->match->load(['player1', 'player2', 'currentQuestion.answers']),
            'timestamp' => now()->toISOString(),
        ];
    }
}
```

**File**: `app/Events/MatchEnded.php`

```php
<?php

namespace App\Events;

use App\Models\Match;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MatchEnded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Match $match
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('match.' . $this->match->id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'match' => $this->match->load(['player1', 'player2', 'winner']),
            'result' => $this->match->result,
            'winner_id' => $this->match->winner_id,
            'timestamp' => now()->toISOString(),
        ];
    }
}
```

---

## ✅ Phase 2 Completion Checklist

- [ ] GameInterface contract created
- [ ] GameFactory implemented
- [ ] TicTacToeGame fully functional
- [ ] AI opponent (easy, medium, hard) working
- [ ] MatchService with all CRUD operations
- [ ] QuestionService for dynamic question selection
- [ ] Real-time events broadcasting correctly
- [ ] Match creation and joining working
- [ ] Turn-based gameplay functional
- [ ] Game state management correct
- [ ] Rewards system working
- [ ] Practice mode with AI functional
- [ ] Unit tests for game logic passing

---

## 🎯 Next Steps

Proceed to [Phase 3: Frontend](PHASE_3_FRONTEND.md) to build the user interface.

---

*Phase 2 Complete! Game Engine Ready* 🎮
