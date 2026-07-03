<?php

namespace Database\Factories;

use App\Models\Answer;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Answer>
 */
class AnswerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'question_id' => Question::factory(),
            'answer' => fake()->words(2, true),
            'is_correct' => false,
            'order' => fake()->numberBetween(1, 4),
        ];
    }

    public function correct(): static
    {
        return $this->state(fn () => ['is_correct' => true]);
    }
}
