<?php

namespace App\Services\GameEngine\Games;

use App\Contracts\GameInterface;
use App\Models\GameMatch;
use App\Models\User;

/**
 * Memory Match (Ingat Pasangan), pair-pick variant: answer correctly to
 * flip two face-down cards at once. A matching pair is claimed for your
 * side; either way both cards are revealed to both players before hiding
 * again, so the memory skill is tracking previous reveals. Most pairs
 * wins; first to a majority clinches early.
 *
 * Board state:
 *   cards:     16 shuffled values (8 pairs)
 *   matched:   claimed card index => owner symbol
 *   revealed:  every index shown at least once
 *   scores:    symbol => pairs claimed
 *   last_flip: ['cards' => [a, b], 'by' => symbol, 'matched' => bool]
 *
 * A move's position is "a|b" (two distinct unclaimed indexes, min first).
 */
class MemoryMatchGame implements GameInterface
{
    public const PAIRS = ['🍎', '🍌', '🥥', '🍉', '🐔', '🐘', '🦜', '⚽'];

    public function initialize(GameMatch $match): array
    {
        $cards = [...self::PAIRS, ...self::PAIRS];
        shuffle($cards);

        return [
            'cards' => $cards,
            'matched' => [],
            'revealed' => [],
            'scores' => [
                $match->player1_symbol => 0,
                $match->player2_symbol => 0,
            ],
            'last_flip' => null,
        ];
    }

    public function makeMove(GameMatch $match, User $user, string $position, bool $isCorrectAnswer): array
    {
        $state = $match->board_state ?? $this->initialize($match);

        if (! $isCorrectAnswer) {
            return [
                'success' => false,
                'board' => $state,
                'message' => 'Jawaban salah, tidak ada langkah dibuat.',
            ];
        }

        $pair = $this->parsePair($position, $state);

        if ($pair === null) {
            return ['success' => false, 'board' => $state, 'message' => 'Pilihan kartu tidak valid.'];
        }

        [$a, $b] = $pair;
        $symbol = $user->id === $match->player1_id ? $match->player1_symbol : $match->player2_symbol;
        $isMatch = $state['cards'][$a] === $state['cards'][$b];

        if ($isMatch) {
            $state['matched'][$a] = $symbol;
            $state['matched'][$b] = $symbol;
            $state['scores'][$symbol] = ($state['scores'][$symbol] ?? 0) + 1;
        }

        $state['revealed'] = array_values(array_unique([...$state['revealed'], $a, $b]));
        $state['last_flip'] = ['cards' => [$a, $b], 'by' => $symbol, 'matched' => $isMatch];

        return [
            'success' => true,
            'board' => $state,
            'symbol' => $symbol,
            'position' => "{$a}|{$b}",
        ];
    }

    public function checkGameOver(GameMatch $match): ?string
    {
        $state = $match->board_state ?? $this->initialize($match);
        $totalPairs = intdiv(count($state['cards']), 2);
        $p1 = $state['scores'][$match->player1_symbol] ?? 0;
        $p2 = $state['scores'][$match->player2_symbol] ?? 0;

        // A majority of pairs clinches the match early.
        if ($p1 * 2 > $totalPairs) {
            return 'player1_win';
        }

        if ($p2 * 2 > $totalPairs) {
            return 'player2_win';
        }

        if ($p1 + $p2 === $totalPairs) {
            return match ($p1 <=> $p2) {
                1 => 'player1_win',
                -1 => 'player2_win',
                default => 'draw',
            };
        }

        return null;
    }

    public function getValidMoves(GameMatch $match): array
    {
        $state = $match->board_state ?? $this->initialize($match);
        $open = $this->unclaimedIndexes($state);
        $moves = [];

        foreach ($open as $i => $a) {
            foreach (array_slice($open, $i + 1) as $b) {
                $moves[] = "{$a}|{$b}";
            }
        }

        return $moves;
    }

    public function getAIMove(GameMatch $match, string $difficulty = 'medium'): ?string
    {
        $state = $match->board_state ?? $this->initialize($match);

        if ($this->unclaimedIndexes($state) === []) {
            return null;
        }

        return match ($difficulty) {
            'easy' => $this->randomPair($state),
            'hard' => $this->knownMatch($state) ?? $this->explorationPair($state),
            default => random_int(1, 100) <= 60
                ? ($this->knownMatch($state) ?? $this->randomPair($state))
                : $this->randomPair($state),
        };
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
     * Parse an "a|b" pair of distinct, in-range, unclaimed indexes.
     *
     * @return array{0: int, 1: int}|null
     */
    protected function parsePair(string $position, array $state): ?array
    {
        $parts = explode('|', $position);

        if (count($parts) !== 2 || ! ctype_digit($parts[0]) || ! ctype_digit($parts[1])) {
            return null;
        }

        $a = min((int) $parts[0], (int) $parts[1]);
        $b = max((int) $parts[0], (int) $parts[1]);

        if ($a === $b || $b >= count($state['cards'])) {
            return null;
        }

        if (isset($state['matched'][$a]) || isset($state['matched'][$b])) {
            return null;
        }

        return [$a, $b];
    }

    /** @return array<int, int> */
    protected function unclaimedIndexes(array $state): array
    {
        return array_values(array_filter(
            array_keys($state['cards']),
            fn (int $i) => ! isset($state['matched'][$i]),
        ));
    }

    /**
     * A pair the AI has "seen": both halves previously revealed and still
     * unclaimed. The AI only remembers reveals, it is not omniscient.
     */
    protected function knownMatch(array $state): ?string
    {
        $open = $this->unclaimedIndexes($state);
        $known = array_values(array_intersect($state['revealed'], $open));

        $byValue = [];
        foreach ($known as $i) {
            $byValue[$state['cards'][$i]][] = $i;
        }

        foreach ($byValue as $indexes) {
            if (count($indexes) >= 2) {
                return "{$indexes[0]}|{$indexes[1]}";
            }
        }

        return null;
    }

    /**
     * No known match: flip unrevealed cards to gain information.
     */
    protected function explorationPair(array $state): ?string
    {
        $open = $this->unclaimedIndexes($state);
        $unrevealed = array_values(array_diff($open, $state['revealed']));
        $pool = count($unrevealed) >= 2 ? $unrevealed : $open;

        return $this->pickTwo($pool);
    }

    protected function randomPair(array $state): ?string
    {
        return $this->pickTwo($this->unclaimedIndexes($state));
    }

    /** @param array<int, int> $pool */
    protected function pickTwo(array $pool): ?string
    {
        if (count($pool) < 2) {
            return null;
        }

        $keys = array_rand($pool, 2);
        [$a, $b] = [$pool[$keys[0]], $pool[$keys[1]]];

        return min($a, $b).'|'.max($a, $b);
    }
}
