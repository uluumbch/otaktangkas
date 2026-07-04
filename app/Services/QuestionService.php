<?php

namespace App\Services;

use App\Models\GameMatch;
use App\Models\Question;
use Illuminate\Database\Eloquent\Builder;

class QuestionService
{
    /**
     * Pick a question for the given match.
     *
     * Preference order: unused questions matching category + resolved
     * difficulty, then difficulty only, then any unused active question,
     * and finally (so a long match never dead-ends) any active question.
     */
    public function getRandomQuestion(GameMatch $match): Question
    {
        $difficulty = $this->resolveDifficulty($match);
        $history = $match->question_history ?? [];

        $filters = [
            fn (Builder $q) => $q->whereNotIn('id', $history)
                ->when($match->category_id, fn (Builder $qq) => $qq->where('category_id', $match->category_id))
                ->where('difficulty', $difficulty),
            fn (Builder $q) => $q->whereNotIn('id', $history)->where('difficulty', $difficulty),
            fn (Builder $q) => $q->whereNotIn('id', $history),
        ];

        foreach ($filters as $filter) {
            $question = $filter($this->baseQuery())->inRandomOrder()->first();

            if ($question) {
                return $question;
            }
        }

        return $this->baseQuery()->inRandomOrder()->firstOrFail();
    }

    /**
     * Pick question ids for a daily puzzle.
     *
     * @return array<int, int>
     */
    public function getQuestionsForDailyPuzzle(int $count, string $difficulty, ?int $categoryId = null): array
    {
        return $this->baseQuery()
            ->where('difficulty', $difficulty)
            ->when($categoryId, fn (Builder $q) => $q->where('category_id', $categoryId))
            ->inRandomOrder()
            ->limit($count)
            ->pluck('id')
            ->all();
    }

    protected function baseQuery(): Builder
    {
        return Question::query()
            ->where('is_active', true)
            ->where('language', app()->getLocale());
    }

    protected function resolveDifficulty(GameMatch $match): string
    {
        if ($match->difficulty !== 'mixed') {
            return $match->difficulty;
        }

        $levels = array_filter([
            $match->player1?->level,
            $match->player2?->level,
        ]);
        $avgLevel = $levels === [] ? 1 : array_sum($levels) / count($levels);

        return match (true) {
            $avgLevel < 10 => 'easy',
            $avgLevel < 25 => 'medium',
            default => 'hard',
        };
    }
}
