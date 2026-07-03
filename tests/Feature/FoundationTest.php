<?php

namespace Tests\Feature;

use App\Enums\Difficulty;
use App\Enums\MatchStatus;
use App\Models\Category;
use App\Models\GameMatch;
use App\Models\Question;
use App\Models\User;
use Database\Seeders\AchievementSeeder;
use Database\Seeders\CategorySeeder;
use Database\Seeders\QuestionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeders_populate_categories_questions_and_answers(): void
    {
        $this->seed([CategorySeeder::class, QuestionSeeder::class, AchievementSeeder::class]);

        $this->assertSame(5, Category::count());
        $this->assertSame(10, Question::count());
        $this->assertSame(5, \App\Models\Achievement::count());

        // Every seeded question must have exactly one correct answer.
        Question::all()->each(function (Question $question) {
            $this->assertSame(1, $question->answers()->where('is_correct', true)->count());
        });
    }

    public function test_new_user_gets_defaults_and_generated_identifiers(): void
    {
        $user = User::create([
            'name' => 'Budi',
            'email' => 'budi@example.test',
            'password' => 'secret123',
        ]);

        $this->assertSame(100, $user->coins);
        $this->assertSame(1, $user->level);
        $this->assertSame('bronze', $user->rank);
        $this->assertStringStartsWith('user_', $user->username);
        $this->assertNotNull($user->referral_code);
    }

    public function test_adding_xp_levels_the_user_up(): void
    {
        $user = User::factory()->create(['xp' => 0, 'level' => 1]);

        $user->addXp(450); // sqrt(450/100)=2.12 -> floor+1 = 3

        $this->assertSame(3, $user->level);
        $this->assertSame(450, $user->xp);
        // To reach level 4 needs (4-1)^2*100 = 900 XP; 900 - 450 = 450 remaining.
        $this->assertSame(450, $user->xpToNextLevel());
    }

    public function test_spending_coins_records_a_transaction(): void
    {
        $user = User::factory()->create();

        $user->addCoins(-30, 'Beli item');

        $this->assertSame(70, $user->coins);
        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'type' => 'spent',
            'amount' => -30,
            'balance_after' => 70,
        ]);
    }

    public function test_question_difficulty_is_cast_to_enum(): void
    {
        $question = Question::factory()
            ->difficulty(Difficulty::Hard)
            ->withAnswers()
            ->create();

        $this->assertInstanceOf(Difficulty::class, $question->difficulty);
        $this->assertSame(2.0, $question->difficultyMultiplier());
        $this->assertSame(4, $question->answers()->count());
        $this->assertSame(1, $question->answers()->where('is_correct', true)->count());
    }

    public function test_game_match_has_defaults_and_relationships(): void
    {
        $user = User::factory()->create();
        $match = GameMatch::create([
            'player1_id' => $user->id,
        ]);

        $this->assertNotEmpty($match->match_code);
        $this->assertSame(MatchStatus::Waiting, $match->status);
        $this->assertTrue($match->player1->is($user));
        $this->assertFalse($match->isPlayerTurn($user));
    }
}
