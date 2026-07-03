# Phase 5: Solo Gameplay Modes

## Overview
Complete solo gameplay implementation including Practice Mode with AI opponent, Daily Puzzle system with global ranking, and optional endless/challenge modes. Designed to provide engaging single-player experiences when multiplayer matches aren't available.

## Table of Contents
- [Practice Mode](#practice-mode)
- [Daily Puzzle System](#daily-puzzle-system)
- [Puzzle Generation Algorithm](#puzzle-generation-algorithm)
- [Daily Puzzle Ranking](#daily-puzzle-ranking)
- [Solo Session Tracking](#solo-session-tracking)
- [Endless Mode](#endless-mode-optional)
- [Challenge Mode](#challenge-mode-optional)
- [Solo Rewards](#solo-rewards)
- [Livewire Components](#livewire-components)

---

## Practice Mode

### Practice Mode Overview
Practice Mode allows players to play against an AI opponent with adjustable difficulty levels. Perfect for learning, warming up, or playing when no human opponents are available.

### AI Opponent Levels

```php
<?php
// config/ai-difficulty.php

return [
    'easy' => [
        'name' => 'Mudah',
        'description' => 'AI sering salah jawab',
        'correct_answer_rate' => 0.4, // 40% chance to answer correctly
        'response_time' => [3, 8], // 3-8 seconds to respond
        'xp_multiplier' => 0.5,
        'coin_multiplier' => 0.5,
    ],
    'medium' => [
        'name' => 'Sedang',
        'description' => 'AI cukup pintar',
        'correct_answer_rate' => 0.6, // 60% chance
        'response_time' => [2, 5],
        'xp_multiplier' => 0.75,
        'coin_multiplier' => 0.75,
    ],
    'hard' => [
        'name' => 'Sulit',
        'description' => 'AI sangat pintar',
        'correct_answer_rate' => 0.8, // 80% chance
        'response_time' => [1, 3],
        'xp_multiplier' => 1.0,
        'coin_multiplier' => 1.0,
    ],
    'expert' => [
        'name' => 'Expert',
        'description' => 'AI hampir sempurna',
        'correct_answer_rate' => 0.95, // 95% chance
        'response_time' => [0.5, 2],
        'xp_multiplier' => 1.5,
        'coin_multiplier' => 1.5,
    ],
];
```

### AI Service

```php
<?php
// app/Services/AIService.php

namespace App\Services;

use App\Models\Game;
use App\Models\Question;
use Illuminate\Support\Facades\Log;

class AIService
{
    /**
     * Make AI move in practice game
     */
    public function makeMove(Game $game, string $difficulty = 'medium'): array
    {
        $config = config("ai-difficulty.{$difficulty}");
        
        // Simulate thinking time
        $thinkingTime = rand(
            $config['response_time'][0] * 1000,
            $config['response_time'][1] * 1000
        );
        usleep($thinkingTime * 1000); // Convert to microseconds

        // Select cell based on strategy
        $cellIndex = $this->selectCell($game);

        // Get question for this cell
        $question = Question::inRandomOrder()->first();

        // AI answers the question
        $answeredCorrectly = $this->answerQuestion($question, $config['correct_answer_rate']);

        return [
            'cell_index' => $cellIndex,
            'question' => $question,
            'answered_correctly' => $answeredCorrectly,
            'thinking_time' => $thinkingTime / 1000, // seconds
        ];
    }

    /**
     * Select best cell using minimax algorithm or random
     */
    protected function selectCell(Game $game): int
    {
        $board = $game->board;
        
        // Find empty cells
        $emptyCells = [];
        for ($i = 0; $i < 9; $i++) {
            if (empty($board[$i])) {
                $emptyCells[] = $i;
            }
        }

        // If no strategic move needed, pick center or corners first
        $preferredCells = [4, 0, 2, 6, 8]; // center, then corners
        foreach ($preferredCells as $cell) {
            if (in_array($cell, $emptyCells)) {
                return $cell;
            }
        }

        // Random cell from remaining
        return $emptyCells[array_rand($emptyCells)];
    }

    /**
     * Simulate AI answering question
     */
    protected function answerQuestion(Question $question, float $correctRate): bool
    {
        // Higher difficulty = higher chance to answer correctly
        return (mt_rand() / mt_getrandmax()) < $correctRate;
    }

    /**
     * Advanced minimax algorithm for expert AI
     */
    protected function minimaxMove(array $board, string $aiSymbol): int
    {
        $playerSymbol = $aiSymbol === 'X' ? 'O' : 'X';
        $bestScore = -INF;
        $bestMove = 0;

        for ($i = 0; $i < 9; $i++) {
            if (empty($board[$i])) {
                $board[$i] = $aiSymbol;
                $score = $this->minimax($board, 0, false, $aiSymbol, $playerSymbol);
                $board[$i] = null;

                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestMove = $i;
                }
            }
        }

        return $bestMove;
    }

    /**
     * Minimax algorithm implementation
     */
    protected function minimax(array $board, int $depth, bool $isMaximizing, string $aiSymbol, string $playerSymbol): int
    {
        $winner = $this->checkWinner($board);
        
        if ($winner === $aiSymbol) return 10 - $depth;
        if ($winner === $playerSymbol) return $depth - 10;
        if ($this->isBoardFull($board)) return 0;

        if ($isMaximizing) {
            $bestScore = -INF;
            for ($i = 0; $i < 9; $i++) {
                if (empty($board[$i])) {
                    $board[$i] = $aiSymbol;
                    $score = $this->minimax($board, $depth + 1, false, $aiSymbol, $playerSymbol);
                    $board[$i] = null;
                    $bestScore = max($score, $bestScore);
                }
            }
            return $bestScore;
        } else {
            $bestScore = INF;
            for ($i = 0; $i < 9; $i++) {
                if (empty($board[$i])) {
                    $board[$i] = $playerSymbol;
                    $score = $this->minimax($board, $depth + 1, true, $aiSymbol, $playerSymbol);
                    $board[$i] = null;
                    $bestScore = min($score, $bestScore);
                }
            }
            return $bestScore;
        }
    }

    protected function checkWinner(array $board): ?string
    {
        $winPatterns = [
            [0, 1, 2], [3, 4, 5], [6, 7, 8], // rows
            [0, 3, 6], [1, 4, 7], [2, 5, 8], // columns
            [0, 4, 8], [2, 4, 6], // diagonals
        ];

        foreach ($winPatterns as $pattern) {
            if (!empty($board[$pattern[0]]) &&
                $board[$pattern[0]] === $board[$pattern[1]] &&
                $board[$pattern[1]] === $board[$pattern[2]]) {
                return $board[$pattern[0]];
            }
        }

        return null;
    }

    protected function isBoardFull(array $board): bool
    {
        foreach ($board as $cell) {
            if (empty($cell)) return false;
        }
        return true;
    }
}
```

### Practice Game Model

```php
<?php
// app/Models/PracticeGame.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PracticeGame extends Model
{
    protected $fillable = [
        'user_id',
        'difficulty',
        'board',
        'current_turn',
        'status',
        'winner',
        'questions_answered',
        'questions_correct',
    ];

    protected $casts = [
        'board' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function moves()
    {
        return $this->hasMany(PracticeGameMove::class);
    }
}
```

### Practice Game Migration

```php
<?php
// database/migrations/2024_xx_xx_create_practice_games_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('practice_games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('difficulty', ['easy', 'medium', 'hard', 'expert']);
            $table->json('board'); // Array of 9 cells
            $table->enum('current_turn', ['player', 'ai']);
            $table->enum('status', ['in_progress', 'completed']);
            $table->enum('winner', ['player', 'ai', 'draw'])->nullable();
            $table->integer('questions_answered')->default(0);
            $table->integer('questions_correct')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::create('practice_game_moves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('practice_game_id')->constrained()->onDelete('cascade');
            $table->integer('cell_index');
            $table->enum('player', ['player', 'ai']);
            $table->foreignId('question_id')->constrained();
            $table->boolean('answered_correctly');
            $table->integer('time_taken')->nullable(); // milliseconds
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('practice_game_moves');
        Schema::dropIfExists('practice_games');
    }
};
```

### Practice Mode Controller

```php
<?php
// app/Http/Controllers/PracticeModeController.php

namespace App\Http\Controllers;

use App\Models\PracticeGame;
use App\Services\AIService;
use App\Services\ProgressionService;
use Illuminate\Http\Request;

class PracticeModeController extends Controller
{
    public function index()
    {
        return view('practice.index', [
            'difficulties' => config('ai-difficulty'),
        ]);
    }

    public function start(Request $request)
    {
        $request->validate([
            'difficulty' => 'required|in:easy,medium,hard,expert',
        ]);

        $game = PracticeGame::create([
            'user_id' => auth()->id(),
            'difficulty' => $request->difficulty,
            'board' => array_fill(0, 9, null),
            'current_turn' => 'player',
            'status' => 'in_progress',
        ]);

        return redirect()->route('practice.play', $game);
    }

    public function play(PracticeGame $game)
    {
        if ($game->user_id !== auth()->id()) {
            abort(403);
        }

        return view('practice.play', [
            'game' => $game,
        ]);
    }

    public function makeMove(Request $request, PracticeGame $game, AIService $aiService)
    {
        $request->validate([
            'cell_index' => 'required|integer|between:0,8',
            'question_id' => 'required|exists:questions,id',
            'answer' => 'required|integer',
        ]);

        // Verify move is valid
        if ($game->board[$request->cell_index] !== null) {
            return response()->json(['error' => 'Cell already occupied'], 400);
        }

        // Check answer
        $question = Question::find($request->question_id);
        $correct = $question->correct_answer_index === $request->answer;

        if ($correct) {
            // Update board
            $board = $game->board;
            $board[$request->cell_index] = 'X';
            $game->board = $board;
            $game->questions_correct++;
        }

        $game->questions_answered++;
        $game->save();

        // Record move
        $game->moves()->create([
            'cell_index' => $request->cell_index,
            'player' => 'player',
            'question_id' => $question->id,
            'answered_correctly' => $correct,
            'time_taken' => $request->time_taken,
        ]);

        // Check for winner
        if ($this->checkGameEnd($game)) {
            return $this->endGame($game);
        }

        // AI turn
        $game->current_turn = 'ai';
        $game->save();

        $aiMove = $aiService->makeMove($game, $game->difficulty);

        if ($aiMove['answered_correctly']) {
            $board = $game->board;
            $board[$aiMove['cell_index']] = 'O';
            $game->board = $board;
        }

        $game->questions_answered++;
        if ($aiMove['answered_correctly']) {
            $game->questions_correct++;
        }
        
        $game->current_turn = 'player';
        $game->save();

        // Record AI move
        $game->moves()->create([
            'cell_index' => $aiMove['cell_index'],
            'player' => 'ai',
            'question_id' => $aiMove['question']->id,
            'answered_correctly' => $aiMove['answered_correctly'],
            'time_taken' => $aiMove['thinking_time'] * 1000,
        ]);

        // Check for winner again
        if ($this->checkGameEnd($game)) {
            return $this->endGame($game);
        }

        return response()->json([
            'success' => true,
            'game' => $game->fresh(),
            'ai_move' => $aiMove,
        ]);
    }

    protected function checkGameEnd(PracticeGame $game): bool
    {
        // Check win patterns
        $winPatterns = [
            [0, 1, 2], [3, 4, 5], [6, 7, 8],
            [0, 3, 6], [1, 4, 7], [2, 5, 8],
            [0, 4, 8], [2, 4, 6],
        ];

        foreach ($winPatterns as $pattern) {
            if (!empty($game->board[$pattern[0]]) &&
                $game->board[$pattern[0]] === $game->board[$pattern[1]] &&
                $game->board[$pattern[1]] === $game->board[$pattern[2]]) {
                
                $game->status = 'completed';
                $game->winner = $game->board[$pattern[0]] === 'X' ? 'player' : 'ai';
                $game->save();
                return true;
            }
        }

        // Check for draw
        $isFull = true;
        foreach ($game->board as $cell) {
            if ($cell === null) {
                $isFull = false;
                break;
            }
        }

        if ($isFull) {
            $game->status = 'completed';
            $game->winner = 'draw';
            $game->save();
            return true;
        }

        return false;
    }

    protected function endGame(PracticeGame $game)
    {
        $config = config("ai-difficulty.{$game->difficulty}");
        $progressionService = app(ProgressionService::class);

        // Calculate rewards
        $baseXP = 25;
        $baseCoins = 25;

        if ($game->winner === 'player') {
            $baseXP = 40;
            $baseCoins = 40;
        } elseif ($game->winner === 'draw') {
            $baseXP = 30;
            $baseCoins = 30;
        }

        // Apply difficulty multiplier
        $xp = (int)($baseXP * $config['xp_multiplier']);
        $coins = (int)($baseCoins * $config['coin_multiplier']);

        // Award rewards
        auth()->user()->awardXP($xp, "Practice game - {$game->difficulty}");
        app(CoinService::class)->award(auth()->user(), $coins, "Practice game");

        return response()->json([
            'game_ended' => true,
            'winner' => $game->winner,
            'rewards' => [
                'xp' => $xp,
                'coins' => $coins,
            ],
        ]);
    }
}
```

---

## Daily Puzzle System

### Daily Puzzle Concept
Each day, all players receive the same puzzle - a pre-configured Tic-Tac-Toe game state with specific questions. Players compete for the fastest completion time and highest accuracy.

### Daily Puzzle Model

```php
<?php
// app/Models/DailyPuzzle.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class DailyPuzzle extends Model
{
    protected $fillable = [
        'date',
        'initial_board',
        'solution_moves',
        'questions',
        'difficulty',
        'xp_reward',
        'coin_reward',
    ];

    protected $casts = [
        'date' => 'date',
        'initial_board' => 'array',
        'solution_moves' => 'array',
        'questions' => 'array',
    ];

    public function attempts()
    {
        return $this->hasMany(DailyPuzzleAttempt::class);
    }

    public function scopeToday($query)
    {
        return $query->where('date', Carbon::today());
    }

    public function scopeForDate($query, Carbon $date)
    {
        return $query->where('date', $date->toDateString());
    }
}
```

### Daily Puzzle Attempt Model

```php
<?php
// app/Models/DailyPuzzleAttempt.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyPuzzleAttempt extends Model
{
    protected $fillable = [
        'user_id',
        'daily_puzzle_id',
        'completed',
        'completion_time', // seconds
        'moves_made',
        'optimal_moves',
        'accuracy', // percentage
        'rank',
        'xp_earned',
        'coins_earned',
    ];

    protected $casts = [
        'moves_made' => 'array',
        'completed' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function puzzle()
    {
        return $this->belongsTo(DailyPuzzle::class);
    }
}
```

### Daily Puzzle Migration

```php
<?php
// database/migrations/2024_xx_xx_create_daily_puzzles_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('daily_puzzles', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->json('initial_board');
            $table->json('solution_moves');
            $table->json('questions');
            $table->enum('difficulty', ['easy', 'medium', 'hard']);
            $table->integer('xp_reward');
            $table->integer('coin_reward');
            $table->timestamps();

            $table->index('date');
        });

        Schema::create('daily_puzzle_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('daily_puzzle_id')->constrained()->onDelete('cascade');
            $table->boolean('completed')->default(false);
            $table->integer('completion_time')->nullable(); // seconds
            $table->json('moves_made')->nullable();
            $table->integer('optimal_moves')->nullable();
            $table->decimal('accuracy', 5, 2)->nullable(); // percentage
            $table->integer('rank')->nullable();
            $table->integer('xp_earned')->default(0);
            $table->integer('coins_earned')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'daily_puzzle_id']);
            $table->index(['daily_puzzle_id', 'completed', 'completion_time']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('daily_puzzle_attempts');
        Schema::dropIfExists('daily_puzzles');
    }
};
```

---

## Puzzle Generation Algorithm

### Puzzle Generator Service

```php
<?php
// app/Services/PuzzleGeneratorService.php

namespace App\Services;

use App\Models\DailyPuzzle;
use App\Models\Question;
use Carbon\Carbon;

class PuzzleGeneratorService
{
    /**
     * Generate daily puzzle for given date
     */
    public function generateForDate(Carbon $date): DailyPuzzle
    {
        // Check if puzzle already exists
        $existing = DailyPuzzle::forDate($date)->first();
        if ($existing) {
            return $existing;
        }

        // Determine difficulty based on day of week
        $difficulty = $this->getDifficultyForDate($date);

        // Generate puzzle scenario
        $scenario = $this->generateScenario($difficulty);

        // Select questions
        $questions = $this->selectQuestions($scenario['solution_moves'], $difficulty);

        return DailyPuzzle::create([
            'date' => $date,
            'initial_board' => $scenario['board'],
            'solution_moves' => $scenario['solution_moves'],
            'questions' => $questions,
            'difficulty' => $difficulty,
            'xp_reward' => $this->getXPReward($difficulty),
            'coin_reward' => $this->getCoinReward($difficulty),
        ]);
    }

    /**
     * Get difficulty based on day of week
     */
    protected function getDifficultyForDate(Carbon $date): string
    {
        // Monday, Tuesday = Easy
        // Wednesday, Thursday = Medium
        // Friday, Saturday, Sunday = Hard
        $dayOfWeek = $date->dayOfWeek;

        return match(true) {
            $dayOfWeek <= 1 => 'easy',
            $dayOfWeek <= 3 => 'medium',
            default => 'hard',
        };
    }

    /**
     * Generate puzzle scenario
     */
    protected function generateScenario(string $difficulty): array
    {
        $scenarios = $this->getPuzzleScenarios();
        $filtered = array_filter($scenarios, fn($s) => $s['difficulty'] === $difficulty);
        
        return $filtered[array_rand($filtered)];
    }

    /**
     * Predefined puzzle scenarios
     */
    protected function getPuzzleScenarios(): array
    {
        return [
            // Easy scenarios - 2 moves to win
            [
                'difficulty' => 'easy',
                'board' => [
                    'X', 'X', null,
                    'O', 'O', null,
                    null, null, null
                ],
                'solution_moves' => [2], // Win by completing top row
                'description' => 'Complete the top row to win',
            ],
            [
                'difficulty' => 'easy',
                'board' => [
                    'X', 'O', 'X',
                    null, 'X', null,
                    'O', null, null
                ],
                'solution_moves' => [8], // Win by diagonal
                'description' => 'Complete the diagonal to win',
            ],

            // Medium scenarios - 3-4 moves to win
            [
                'difficulty' => 'medium',
                'board' => [
                    'X', 'O', null,
                    'O', null, null,
                    null, null, null
                ],
                'solution_moves' => [4, 6, 2], // Center, bottom-left, top-right
                'description' => 'Strategic 3-move win',
            ],
            [
                'difficulty' => 'medium',
                'board' => [
                    null, 'X', null,
                    null, 'O', null,
                    'X', 'O', null
                ],
                'solution_moves' => [0, 3, 8], // Multiple winning paths
                'description' => 'Create multiple threats',
            ],

            // Hard scenarios - 5+ moves, complex strategy
            [
                'difficulty' => 'hard',
                'board' => [
                    null, null, null,
                    null, null, null,
                    null, null, null
                ],
                'solution_moves' => [4, 0, 8, 2], // Perfect center start strategy
                'description' => 'Perfect opening strategy',
            ],
            [
                'difficulty' => 'hard',
                'board' => [
                    'O', null, null,
                    null, 'X', null,
                    null, null, 'O'
                ],
                'solution_moves' => [1, 3, 5, 7], // Complex winning sequence
                'description' => 'Advanced tactical sequence',
            ],
        ];
    }

    /**
     * Select questions for puzzle
     */
    protected function selectQuestions(array $solutionMoves, string $difficulty): array
    {
        $questionDifficulty = match($difficulty) {
            'easy' => 'easy',
            'medium' => 'medium',
            'hard' => 'hard',
        };

        $questions = Question::where('difficulty', $questionDifficulty)
            ->inRandomOrder()
            ->limit(count($solutionMoves))
            ->get();

        return $questions->map(function($q) {
            return [
                'id' => $q->id,
                'question' => $q->question,
                'options' => $q->options,
                'correct_answer_index' => $q->correct_answer_index,
            ];
        })->toArray();
    }

    protected function getXPReward(string $difficulty): int
    {
        return match($difficulty) {
            'easy' => 100,
            'medium' => 150,
            'hard' => 200,
        };
    }

    protected function getCoinReward(string $difficulty): int
    {
        return match($difficulty) {
            'easy' => 100,
            'medium' => 150,
            'hard' => 200,
        };
    }

    /**
     * Generate puzzles for next N days
     */
    public function generateUpcoming(int $days = 7): void
    {
        $startDate = Carbon::today();
        
        for ($i = 0; $i < $days; $i++) {
            $date = $startDate->copy()->addDays($i);
            $this->generateForDate($date);
        }
    }
}
```

### Puzzle Generation Command

```php
<?php
// app/Console/Commands/GenerateDailyPuzzle.php

namespace App\Console\Commands;

use App\Services\PuzzleGeneratorService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateDailyPuzzle extends Command
{
    protected $signature = 'puzzle:generate {date?}';
    protected $description = 'Generate daily puzzle for specified date or today';

    public function handle(PuzzleGeneratorService $generator)
    {
        $date = $this->argument('date') 
            ? Carbon::parse($this->argument('date'))
            : Carbon::today();

        $puzzle = $generator->generateForDate($date);

        $this->info("Daily puzzle generated for {$date->toDateString()}");
        $this->info("Difficulty: {$puzzle->difficulty}");
        $this->info("Rewards: {$puzzle->xp_reward} XP, {$puzzle->coin_reward} coins");

        return 0;
    }
}
```

### Schedule in Kernel

```php
<?php
// app/Console/Kernel.php

protected function schedule(Schedule $schedule)
{
    // Generate tomorrow's puzzle at midnight
    $schedule->command('puzzle:generate')
        ->dailyAt('00:00');

    // Generate next week's puzzles every Sunday
    $schedule->call(function () {
        $generator = app(PuzzleGeneratorService::class);
        $generator->generateUpcoming(7);
    })->weekly()->sundays()->at('02:00');
}
```

---

## Daily Puzzle Ranking

### Ranking Service

```php
<?php
// app/Services/PuzzleRankingService.php

namespace App\Services;

use App\Models\DailyPuzzle;
use App\Models\DailyPuzzleAttempt;
use Illuminate\Support\Facades\Cache;

class PuzzleRankingService
{
    /**
     * Update rankings for a puzzle
     */
    public function updateRankings(DailyPuzzle $puzzle): void
    {
        // Get all completed attempts, sorted by completion time
        $attempts = DailyPuzzleAttempt::where('daily_puzzle_id', $puzzle->id)
            ->where('completed', true)
            ->orderBy('completion_time', 'asc')
            ->orderBy('accuracy', 'desc')
            ->get();

        // Update ranks
        foreach ($attempts as $index => $attempt) {
            $attempt->rank = $index + 1;
            $attempt->save();
        }

        // Award bonus rewards for top 10
        $this->awardTopRankerBonuses($puzzle, $attempts->take(10));

        // Clear cache
        Cache::forget("puzzle_ranking:{$puzzle->id}");
    }

    /**
     * Award bonus rewards to top rankers
     */
    protected function awardTopRankerBonuses(DailyPuzzle $puzzle, $topAttempts): void
    {
        $bonuses = [
            1 => ['xp' => 100, 'coins' => 200], // 1st place
            2 => ['xp' => 75, 'coins' => 150],  // 2nd place
            3 => ['xp' => 50, 'coins' => 100],  // 3rd place
        ];

        foreach ($topAttempts as $attempt) {
            if (isset($bonuses[$attempt->rank])) {
                $bonus = $bonuses[$attempt->rank];
                
                // Award bonus if not already awarded
                if ($attempt->xp_earned === $puzzle->xp_reward) {
                    $attempt->xp_earned += $bonus['xp'];
                    $attempt->coins_earned += $bonus['coins'];
                    $attempt->save();

                    $attempt->user->awardXP($bonus['xp'], "Daily Puzzle Top {$attempt->rank}");
                    app(CoinService::class)->award(
                        $attempt->user, 
                        $bonus['coins'], 
                        "Daily Puzzle Top {$attempt->rank}"
                    );
                }
            }
        }
    }

    /**
     * Get ranking for a puzzle
     */
    public function getRanking(DailyPuzzle $puzzle, int $limit = 100): array
    {
        return Cache::remember(
            "puzzle_ranking:{$puzzle->id}",
            3600,
            function () use ($puzzle, $limit) {
                return DailyPuzzleAttempt::where('daily_puzzle_id', $puzzle->id)
                    ->where('completed', true)
                    ->with('user')
                    ->orderBy('rank', 'asc')
                    ->limit($limit)
                    ->get()
                    ->map(function ($attempt) {
                        return [
                            'rank' => $attempt->rank,
                            'user' => $attempt->user,
                            'completion_time' => $attempt->completion_time,
                            'accuracy' => $attempt->accuracy,
                            'score' => $this->calculateScore($attempt),
                        ];
                    })
                    ->toArray();
            }
        );
    }

    /**
     * Calculate score for ranking display
     */
    protected function calculateScore(DailyPuzzleAttempt $attempt): int
    {
        // Score = (Accuracy * 100) - (CompletionTime / 10)
        return (int)(($attempt->accuracy * 100) - ($attempt->completion_time / 10));
    }

    /**
     * Get user's rank for a puzzle
     */
    public function getUserRank(DailyPuzzle $puzzle, $userId): ?int
    {
        $attempt = DailyPuzzleAttempt::where('daily_puzzle_id', $puzzle->id)
            ->where('user_id', $userId)
            ->first();

        return $attempt?->rank;
    }
}
```

---

## Solo Session Tracking

### Solo Statistics

```php
<?php
// Add to User model

class User extends Authenticatable
{
    /**
     * Get solo play statistics
     */
    public function getSoloStatsAttribute(): array
    {
        $practiceGames = PracticeGame::where('user_id', $this->id)
            ->where('status', 'completed')
            ->get();

        $dailyPuzzles = DailyPuzzleAttempt::where('user_id', $this->id)
            ->where('completed', true)
            ->get();

        return [
            'practice_games_played' => $practiceGames->count(),
            'practice_games_won' => $practiceGames->where('winner', 'player')->count(),
            'practice_win_rate' => $practiceGames->count() > 0 
                ? round($practiceGames->where('winner', 'player')->count() / $practiceGames->count() * 100, 2)
                : 0,
            'daily_puzzles_completed' => $dailyPuzzles->count(),
            'average_puzzle_time' => $dailyPuzzles->avg('completion_time'),
            'best_puzzle_rank' => $dailyPuzzles->min('rank'),
            'total_solo_xp' => PracticeGame::where('user_id', $this->id)->sum('xp_earned') 
                + $dailyPuzzles->sum('xp_earned'),
        ];
    }
}
```

---

## Endless Mode (Optional)

### Endless Mode Concept
Play continuous practice games with increasing difficulty. See how many games you can win in a row.

```php
<?php
// app/Models/EndlessSession.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EndlessSession extends Model
{
    protected $fillable = [
        'user_id',
        'current_game_number',
        'current_difficulty',
        'total_wins',
        'total_games',
        'current_streak',
        'best_streak',
        'status',
        'total_xp_earned',
        'total_coins_earned',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function games()
    {
        return $this->hasMany(PracticeGame::class, 'endless_session_id');
    }

    /**
     * Increase difficulty based on progress
     */
    public function getNextDifficulty(): string
    {
        return match(true) {
            $this->current_game_number <= 3 => 'easy',
            $this->current_game_number <= 6 => 'medium',
            $this->current_game_number <= 10 => 'hard',
            default => 'expert',
        };
    }
}
```

---

## Challenge Mode (Optional)

### Challenge Mode Concept
Special time-limited challenges with unique rules and extra rewards.

```php
<?php
// app/Models/Challenge.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Challenge extends Model
{
    protected $fillable = [
        'name',
        'description',
        'start_date',
        'end_date',
        'challenge_type',
        'rules',
        'xp_reward',
        'coin_reward',
        'badge',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'rules' => 'array',
    ];

    public function isActive(): bool
    {
        $now = Carbon::now();
        return $now->between($this->start_date, $this->end_date);
    }

    public function attempts()
    {
        return $this->hasMany(ChallengeAttempt::class);
    }
}
```

---

## Solo Rewards

### Solo Rewards Configuration

```php
<?php
// config/solo-rewards.php

return [
    'practice' => [
        'win' => [
            'easy' => ['xp' => 20, 'coins' => 20],
            'medium' => ['xp' => 30, 'coins' => 30],
            'hard' => ['xp' => 40, 'coins' => 40],
            'expert' => ['xp' => 60, 'coins' => 60],
        ],
        'loss' => ['xp' => 10, 'coins' => 10],
        'draw' => ['xp' => 15, 'coins' => 15],
    ],
    'daily_puzzle' => [
        'completion' => ['xp' => 100, 'coins' => 100],
        'top_10_bonus' => [
            1 => ['xp' => 100, 'coins' => 200],
            2 => ['xp' => 75, 'coins' => 150],
            3 => ['xp' => 50, 'coins' => 100],
            4 => ['xp' => 40, 'coins' => 80],
            5 => ['xp' => 30, 'coins' => 60],
            6 => ['xp' => 25, 'coins' => 50],
            7 => ['xp' => 20, 'coins' => 40],
            8 => ['xp' => 15, 'coins' => 30],
            9 => ['xp' => 10, 'coins' => 20],
            10 => ['xp' => 5, 'coins' => 10],
        ],
    ],
];
```

---

## Livewire Components

### Practice Mode Livewire Component

```php
<?php
// app/Livewire/PracticeMode.php

namespace App\Livewire;

use App\Models\PracticeGame;
use App\Services\AIService;
use Livewire\Component;

class PracticeMode extends Component
{
    public PracticeGame $game;
    public $showQuestionModal = false;
    public $currentQuestion = null;
    public $selectedCell = null;

    protected $listeners = ['refreshGame'];

    public function mount(PracticeGame $game)
    {
        $this->game = $game;
    }

    public function selectCell($index)
    {
        if ($this->game->status !== 'in_progress') {
            return;
        }

        if ($this->game->current_turn !== 'player') {
            return;
        }

        if (!empty($this->game->board[$index])) {
            return;
        }

        $this->selectedCell = $index;
        $this->currentQuestion = Question::inRandomOrder()->first();
        $this->showQuestionModal = true;
    }

    public function answerQuestion($answerIndex)
    {
        $correct = $this->currentQuestion->correct_answer_index === $answerIndex;

        if ($correct) {
            $board = $this->game->board;
            $board[$this->selectedCell] = 'X';
            $this->game->board = $board;
            $this->game->save();
        }

        $this->showQuestionModal = false;
        $this->currentQuestion = null;
        $this->selectedCell = null;

        // Check game end
        if ($this->checkGameEnd()) {
            return;
        }

        // AI turn
        $this->makeAIMove();
    }

    protected function makeAIMove()
    {
        $aiService = app(AIService::class);
        $move = $aiService->makeMove($this->game, $this->game->difficulty);

        if ($move['answered_correctly']) {
            $board = $this->game->board;
            $board[$move['cell_index']] = 'O';
            $this->game->board = $board;
            $this->game->save();
        }

        $this->checkGameEnd();
    }

    protected function checkGameEnd()
    {
        // Implementation similar to controller
        return false;
    }

    public function render()
    {
        return view('livewire.practice-mode');
    }
}
```

### Daily Puzzle Livewire Component

```blade
<!-- resources/views/livewire/daily-puzzle.blade.php -->
<div class="max-w-2xl mx-auto">
    
    <!-- Puzzle Header -->
    <div class="bg-linear-to-r from-secondary-500 to-secondary-600 rounded-xl p-6 text-white mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold">Puzzle Harian</h2>
                <p class="text-secondary-100">{{ $puzzle->date->format('d F Y') }}</p>
            </div>
            <div class="text-right">
                <div class="text-3xl font-bold">{{ $puzzle->difficulty }}</div>
                <div class="text-sm">{{ $puzzle->xp_reward }} XP • {{ $puzzle->coin_reward }} Coins</div>
            </div>
        </div>
    </div>

    <!-- Board -->
    <div class="bg-white rounded-2xl shadow-game p-6 mb-6">
        <div class="grid grid-cols-3 gap-3">
            @for ($i = 0; $i < 9; $i++)
                <button 
                    wire:click="selectCell({{ $i }})"
                    class="aspect-square rounded-xl border-4 border-secondary-300 hover:border-secondary-500 transition-all flex items-center justify-center text-4xl font-bold"
                    {{ in_array($i, $solutionMoves) ? '' : 'disabled' }}>
                    
                    @if($puzzle->initial_board[$i] === 'X')
                        <i class="fas fa-times text-secondary-600"></i>
                    @elseif($puzzle->initial_board[$i] === 'O')
                        <i class="fas fa-circle text-primary-600"></i>
                    @endif
                </button>
            @endfor
        </div>

        <!-- Timer -->
        <div class="mt-6 text-center">
            <div class="text-3xl font-bold" x-data="{ time: 0 }" x-init="setInterval(() => time++, 1000)">
                <span x-text="Math.floor(time / 60)"></span>:<span x-text="(time % 60).toString().padStart(2, '0')"></span>
            </div>
            <p class="text-sm text-gray-600">Waktu</p>
        </div>
    </div>

    <!-- Rankings -->
    <div class="bg-white rounded-xl shadow-card p-6">
        <h3 class="text-lg font-bold mb-4">Peringkat Hari Ini</h3>
        <div class="space-y-2">
            @foreach($rankings as $entry)
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <div class="flex items-center space-x-3">
                    <span class="font-bold text-lg">{{ $entry['rank'] }}</span>
                    <img src="{{ $entry['user']->avatar_url }}" class="w-8 h-8 rounded-full">
                    <span>{{ $entry['user']->name }}</span>
                </div>
                <span class="font-semibold">{{ $entry['completion_time'] }}s</span>
            </div>
            @endforeach
        </div>
    </div>

</div>
```

---

## Completion Checklist

### Database Setup
- [ ] Create practice_games table
- [ ] Create practice_game_moves table
- [ ] Create daily_puzzles table
- [ ] Create daily_puzzle_attempts table
- [ ] Create endless_sessions table (optional)
- [ ] Create challenges table (optional)

### AI Implementation
- [ ] Implement AIService with difficulty levels
- [ ] Implement minimax algorithm for expert AI
- [ ] Test AI decision-making
- [ ] Tune AI difficulty parameters

### Daily Puzzle System
- [ ] Implement PuzzleGeneratorService
- [ ] Create puzzle scenarios
- [ ] Set up daily generation command
- [ ] Schedule automatic generation
- [ ] Implement ranking system

### Practice Mode
- [ ] Create practice game flow
- [ ] Implement AI opponent
- [ ] Add difficulty selection
- [ ] Test all difficulty levels
- [ ] Implement rewards

### Rewards
- [ ] Configure solo rewards
- [ ] Implement practice game rewards
- [ ] Implement daily puzzle rewards
- [ ] Implement top-rank bonuses
- [ ] Test reward calculations

### UI Components
- [ ] Practice mode selection screen
- [ ] Practice game board
- [ ] Daily puzzle interface
- [ ] Ranking display
- [ ] Completion screens

### Testing
- [ ] Test AI behavior at all difficulties
- [ ] Test puzzle generation
- [ ] Test ranking calculations
- [ ] Test reward distribution
- [ ] Performance testing

---

## Related Documentation
- [PHASE_2_GAME_ENGINE.md](./PHASE_2_GAME_ENGINE.md)
- [PHASE_3_FRONTEND.md](./PHASE_3_FRONTEND.md)
- [PHASE_4_PROGRESSION.md](./PHASE_4_PROGRESSION.md)

---

**Last Updated:** July 2026
**Status:** Ready for Implementation
