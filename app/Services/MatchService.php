<?php

namespace App\Services;

use App\Events\MatchEnded;
use App\Events\MatchUpdated;
use App\Models\GameMatch;
use App\Models\User;
use App\Services\GameEngine\GameFactory;
use App\Services\Progression\AchievementService;
use App\Services\Progression\RankService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MatchService
{
    public function __construct(
        protected QuestionService $questionService,
        protected AchievementService $achievements,
        protected RankService $ranks,
    ) {}

    /**
     * Create a new match. Practice matches start immediately against the AI.
     *
     * @param  array<string, mixed>  $options
     */
    public function createMatch(User $player1, string $mode, array $options = []): GameMatch
    {
        return DB::transaction(function () use ($player1, $mode, $options) {
            $match = GameMatch::create([
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

            $game = GameFactory::create($match->game_type);
            $match->board_state = $game->initialize($match);

            if ($mode === 'practice') {
                $ai = $this->createAIOpponent();
                $match->player2_id = $ai->id;
                $match->setRelation('player2', $ai);
                $match->current_turn_user_id = $player1->id;
                $match->started_at = now();
                $match->turn_started_at = now();

                $question = $this->questionService->getRandomQuestion($match);
                $match->current_question_id = $question->id;
                $match->question_history = [$question->id];
            }

            $match->save();

            return $match;
        });
    }

    /**
     * Join a waiting match as the second player.
     */
    public function joinMatch(GameMatch $match, User $player2): bool
    {
        if ($match->status !== \App\Enums\MatchStatus::Waiting || $match->player2_id) {
            return false;
        }

        DB::transaction(function () use ($match, $player2) {
            $match->player2_id = $player2->id;
            $match->status = 'in_progress';
            $match->started_at = now();
            $match->current_turn_user_id = $match->player1_id;
            $match->turn_started_at = now();

            $question = $this->questionService->getRandomQuestion($match);
            $match->current_question_id = $question->id;
            $match->question_history = [$question->id];

            $match->save();
        });

        event(new MatchUpdated($match));

        return true;
    }

    /**
     * Process a player's answer and resulting move.
     *
     * @return array<string, mixed>
     */
    public function processAnswer(GameMatch $match, User $user, int $answerId, string $position): array
    {
        if ($match->status !== \App\Enums\MatchStatus::InProgress) {
            return ['success' => false, 'message' => 'Pertandingan tidak sedang berlangsung.'];
        }

        if (! $match->isPlayerTurn($user)) {
            return ['success' => false, 'message' => 'Bukan giliranmu.'];
        }

        $result = DB::transaction(function () use ($match, $user, $answerId, $position) {
            $question = $match->currentQuestion;
            $answer = $question?->answers()->find($answerId);
            $isCorrect = (bool) ($answer?->is_correct);

            $move = $match->moves()->create([
                'user_id' => $user->id,
                'question_id' => $question->id,
                'answer_id' => $answerId,
                'move_number' => $match->moves()->count() + 1,
                'position' => $position,
                'is_correct' => $isCorrect,
                'time_taken' => $this->secondsSince($match->turn_started_at),
            ]);

            $question->incrementUsage($isCorrect);

            $game = GameFactory::create($match->game_type);
            $moveResult = $game->makeMove($match, $user, $position, $isCorrect);

            if (! $moveResult['success']) {
                return [
                    'success' => false,
                    'is_correct' => $isCorrect,
                    'move' => $move,
                    'message' => $moveResult['message'] ?? null,
                    'match' => $match,
                    'game_state' => $game->getGameState($match),
                ];
            }

            $match->board_state = $moveResult['board'];

            // Per-move rewards for a correct answer (human players only).
            if ($isCorrect && ! $user->is_guest) {
                $user->addXp($question->xp_reward);
                $user->addCoins($question->coins_reward, 'Jawaban benar dalam pertandingan', $match);
                $move->update([
                    'xp_earned' => $question->xp_reward,
                    'coins_earned' => $question->coins_reward,
                ]);
            }

            $gameResult = $game->checkGameOver($match);

            if ($gameResult) {
                $this->endMatch($match, $gameResult);
            } else {
                $next = $match->opponentFor($user);
                $match->current_turn_user_id = $next?->id;
                $match->turn_started_at = now();

                $nextQuestion = $this->questionService->getRandomQuestion($match);
                $match->current_question_id = $nextQuestion->id;
                $match->question_history = [...($match->question_history ?? []), $nextQuestion->id];

                $match->save();
            }

            return [
                'success' => true,
                'is_correct' => $isCorrect,
                'move' => $move,
                'match' => $match,
                'game_state' => $game->getGameState($match),
            ];
        });

        if ($result['success'] && $match->status === \App\Enums\MatchStatus::InProgress) {
            event(new MatchUpdated($match));
        }

        return $result;
    }

    /**
     * Have the AI take its turn (practice mode). The AI always answers correctly.
     *
     * @return array<string, mixed>
     */
    public function processAIMove(GameMatch $match): array
    {
        $game = GameFactory::create($match->game_type);
        $difficulty = in_array($match->difficulty, ['easy', 'medium', 'hard'], true) ? $match->difficulty : 'medium';
        $position = $game->getAIMove($match, $difficulty);

        if (! $position) {
            return ['success' => false, 'message' => 'Tidak ada langkah tersedia.'];
        }

        $correctAnswer = $match->currentQuestion?->correctAnswer;

        if (! $correctAnswer || ! $match->player2) {
            return ['success' => false, 'message' => 'AI tidak dapat menjawab.'];
        }

        return $this->processAnswer($match, $match->player2, $correctAnswer->id, $position);
    }

    /**
     * Finalize a match and distribute end-of-match rewards and stats.
     */
    protected function endMatch(GameMatch $match, string $result): void
    {
        $match->status = 'completed';
        $match->result = $result;
        $match->ended_at = now();

        [$winner, $loser] = match ($result) {
            'player1_win' => [$match->player1, $match->player2],
            'player2_win' => [$match->player2, $match->player1],
            default => [null, null],
        };

        $baseXp = 50;
        $baseCoins = 25;

        if ($winner) {
            $match->winner_id = $winner->id;

            if (! $winner->is_guest) {
                $winner->addXp((int) ($baseXp * 1.5));
                $winner->addCoins((int) ($baseCoins * 1.5), 'Menang pertandingan', $match);
                $winner->increment('total_matches');
                $winner->increment('wins');
                $winner->increment('win_streak');
                $winner->best_win_streak = max($winner->best_win_streak, $winner->win_streak);
                $winner->save();
            }

            if ($loser && ! $loser->is_guest) {
                $loser->addXp($baseXp);
                $loser->addCoins($baseCoins, 'Menyelesaikan pertandingan', $match);
                $loser->increment('total_matches');
                $loser->increment('losses');
                $loser->win_streak = 0;
                $loser->save();
            }
        } else {
            foreach ([$match->player1, $match->player2] as $player) {
                if ($player && ! $player->is_guest) {
                    $player->addXp($baseXp);
                    $player->addCoins($baseCoins, 'Pertandingan seri', $match);
                    $player->increment('total_matches');
                    $player->increment('draws');
                    $player->win_streak = 0;
                    $player->save();
                }
            }
        }

        $match->xp_awarded = $baseXp;
        $match->coins_awarded = $baseCoins;
        $match->save();

        // Progression: unlock achievements, then re-derive rank from level.
        foreach ([$match->player1, $match->player2] as $player) {
            if ($player && ! $player->is_guest) {
                $this->achievements->evaluate($player);
                $this->ranks->sync($player);
            }
        }

        event(new MatchEnded($match));
    }

    /**
     * A shared, guest-flagged AI user used as the practice opponent.
     */
    protected function createAIOpponent(): User
    {
        return User::firstOrCreate(
            ['email' => 'ai@otaktangkas.com'],
            [
                'name' => 'AI OtakTangkas',
                'username' => 'ai_opponent',
                'password' => Str::random(40),
                'is_guest' => true,
                'email_verified_at' => now(),
            ],
        );
    }

    /**
     * Find a waiting quick-play match near the user's level.
     *
     * @param  array<string, mixed>  $options
     */
    public function findQuickPlayMatch(User $user, array $options = []): ?GameMatch
    {
        $levelRange = 5;

        return GameMatch::where('status', \App\Enums\MatchStatus::Waiting)
            ->where('mode', \App\Enums\MatchMode::QuickPlay)
            ->where('game_type', $options['game_type'] ?? 'tic_tac_toe')
            ->where('player1_id', '!=', $user->id)
            ->whereHas('player1', fn ($query) => $query->whereBetween('level', [
                max(1, $user->level - $levelRange),
                $user->level + $levelRange,
            ]))
            ->when($options['category_id'] ?? null, fn ($query, $categoryId) => $query->where('category_id', $categoryId))
            ->oldest()
            ->first();
    }

    /**
     * Abandon a match. If a player quit, the opponent is awarded the win.
     */
    public function abandonMatch(GameMatch $match, ?User $player = null): void
    {
        $match->status = 'abandoned';
        $match->result = 'abandoned';
        $match->ended_at = now();
        $match->save();

        if (! $player) {
            return;
        }

        $opponent = $match->opponentFor($player);

        if ($opponent) {
            // The opponent always wins a forfeit (even the AI in practice),
            // but only human opponents receive rewards.
            $match->winner_id = $opponent->id;
            $match->result = $player->id === $match->player1_id ? 'player2_win' : 'player1_win';
            $match->save();

            if (! $opponent->is_guest) {
                $opponent->addXp(30);
                $opponent->addCoins(15, 'Lawan meninggalkan pertandingan', $match);
                $opponent->increment('wins');
                $opponent->increment('total_matches');
                $opponent->save();
            }
        }

        if (! $player->is_guest) {
            $player->increment('losses');
            $player->increment('total_matches');
            $player->win_streak = 0;
            $player->save();
        }
    }

    protected function secondsSince(?\Illuminate\Support\Carbon $from): int
    {
        return $from ? (int) $from->diffInSeconds(now()) : 0;
    }
}
