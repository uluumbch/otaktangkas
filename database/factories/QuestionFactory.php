<?php

namespace Database\Factories;

use App\Enums\Difficulty;
use App\Models\Category;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'question' => rtrim(fake()->sentence(), '.').'?',
            'difficulty' => fake()->randomElement(Difficulty::cases()),
            'language' => 'id',
            'time_limit' => 30,
            'xp_reward' => 10,
            'coins_reward' => 5,
            'is_active' => true,
            'explanation' => fake()->optional()->sentence(),
            'tags' => null,
        ];
    }

    public function difficulty(Difficulty $difficulty): static
    {
        return $this->state(fn () => ['difficulty' => $difficulty]);
    }

    /**
     * Attach four answers (one correct) after creating the question.
     */
    public function withAnswers(): static
    {
        return $this->afterCreating(function (Question $question) {
            $question->answers()->create(['answer' => 'Jawaban benar', 'is_correct' => true, 'order' => 1]);
            foreach (range(2, 4) as $i) {
                $question->answers()->create(['answer' => "Jawaban salah {$i}", 'is_correct' => false, 'order' => $i]);
            }
        });
    }
}
