<?php

namespace App\Models;

use App\Enums\MatchMode;
use App\Enums\MatchStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Represents a single game match.
 *
 * Named GameMatch (not Match) because `match` is a reserved keyword in PHP 8+.
 * The underlying table remains `matches`.
 */
#[Fillable([
    'match_code', 'game_type', 'mode',
    'player1_id', 'player2_id', 'player1_symbol', 'player2_symbol',
    'status', 'current_turn_user_id', 'board_state',
    'winner_id', 'result',
    'current_question_id', 'question_history',
    'started_at', 'ended_at', 'turn_time_limit', 'turn_started_at',
    'xp_awarded', 'coins_awarded',
    'category_id', 'difficulty',
])]
class GameMatch extends Model
{
    /** @use HasFactory<\Database\Factories\GameMatchFactory> */
    use HasFactory;

    protected $table = 'matches';

    /**
     * Default attribute values, mirroring the database column defaults so the
     * enum casts (status/mode) resolve on freshly created models.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'game_type' => 'tic_tac_toe',
        'mode' => 'quick_play',
        'status' => 'waiting',
        'player1_symbol' => 'X',
        'player2_symbol' => 'O',
        'turn_time_limit' => 30,
        'xp_awarded' => 0,
        'coins_awarded' => 0,
        'difficulty' => 'mixed',
    ];

    protected function casts(): array
    {
        return [
            'board_state' => 'array',
            'question_history' => 'array',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'turn_started_at' => 'datetime',
            'status' => MatchStatus::class,
            'mode' => MatchMode::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (GameMatch $match) {
            if (! $match->match_code) {
                $match->match_code = Str::upper(Str::random(6));
            }
        });
    }

    public function player1(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player1_id');
    }

    public function player2(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player2_id');
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_id');
    }

    public function currentTurnUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'current_turn_user_id');
    }

    public function currentQuestion(): BelongsTo
    {
        return $this->belongsTo(Question::class, 'current_question_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function moves(): HasMany
    {
        return $this->hasMany(MatchMove::class, 'match_id')->orderBy('move_number');
    }

    public function opponentFor(User $user): ?User
    {
        return $user->id === $this->player1_id ? $this->player2 : $this->player1;
    }

    public function isPlayerTurn(User $user): bool
    {
        return $this->current_turn_user_id === $user->id;
    }
}
