<?php

namespace Tests\Feature;

use App\Models\Achievement;
use App\Models\User;
use App\Services\Progression\AchievementService;
use App\Services\Progression\RankService;
use App\Services\Progression\StreakService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ProgressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_rank_is_derived_from_level(): void
    {
        $ranks = new RankService();

        $this->assertSame('bronze', $ranks->for(1));
        $this->assertSame('bronze', $ranks->for(9));
        $this->assertSame('silver', $ranks->for(10));
        $this->assertSame('gold', $ranks->for(25));
        $this->assertSame('platinum', $ranks->for(35));
        $this->assertSame('diamond', $ranks->for(50));
    }

    public function test_rank_sync_updates_only_when_changed(): void
    {
        $ranks = new RankService();
        $user = User::factory()->create(['level' => 12, 'rank' => 'bronze']);

        $this->assertSame('silver', $ranks->sync($user));
        $this->assertSame('silver', $user->fresh()->rank);
        $this->assertNull($ranks->sync($user)); // no change second time
    }

    public function test_achievements_unlock_when_criteria_met_and_award_rewards(): void
    {
        $achievements = new AchievementService();
        Achievement::create([
            'name' => 'Kemenangan Perdana', 'slug' => 'kemenangan-perdana',
            'description' => 'Menang sekali.', 'rarity' => 'common',
            'criteria' => ['type' => 'wins', 'value' => 1],
            'xp_reward' => 30, 'coins_reward' => 15, 'is_active' => true,
        ]);

        $user = User::factory()->create(['wins' => 1, 'xp' => 0, 'coins' => 100]);

        $unlocked = $achievements->evaluate($user);

        $this->assertCount(1, $unlocked);
        $this->assertDatabaseHas('user_achievements', ['user_id' => $user->id]);
        $this->assertSame(30, $user->fresh()->xp);
        $this->assertSame(115, $user->fresh()->coins);
    }

    public function test_achievements_do_not_unlock_twice(): void
    {
        $achievements = new AchievementService();
        Achievement::create([
            'name' => 'Langkah Pertama', 'slug' => 'langkah-pertama',
            'description' => 'Main sekali.', 'rarity' => 'common',
            'criteria' => ['type' => 'total_matches', 'value' => 1],
            'xp_reward' => 20, 'coins_reward' => 10, 'is_active' => true,
        ]);
        $user = User::factory()->create(['total_matches' => 5]);

        $this->assertCount(1, $achievements->evaluate($user));
        $this->assertCount(0, $achievements->evaluate($user)); // idempotent
        $this->assertSame(1, $user->achievements()->count());
    }

    public function test_unmet_criteria_do_not_unlock(): void
    {
        $achievements = new AchievementService();
        Achievement::create([
            'name' => 'Juara Beruntun', 'slug' => 'juara-beruntun',
            'description' => '5 menang beruntun.', 'rarity' => 'rare',
            'criteria' => ['type' => 'win_streak', 'value' => 5],
            'xp_reward' => 100, 'coins_reward' => 50, 'is_active' => true,
        ]);
        $user = User::factory()->create(['win_streak' => 2]);

        $this->assertCount(0, $achievements->evaluate($user));
    }

    public function test_login_streak_increments_on_consecutive_days(): void
    {
        $streaks = new StreakService();
        $user = User::factory()->create(['daily_login_streak' => 0, 'last_login_date' => null]);

        // First login today.
        $this->assertSame(1, $streaks->recordLogin($user));
        // Same day again: no change.
        $this->assertSame(1, $streaks->recordLogin($user));

        // Simulate that the last login was yesterday, then log in "today".
        // (last_login_date is service-managed, not mass-assignable, so set it directly.)
        $user->last_login_date = Carbon::yesterday();
        $user->save();
        $this->assertSame(2, $streaks->recordLogin($user->fresh()));
    }

    public function test_login_streak_resets_after_a_gap(): void
    {
        $streaks = new StreakService();
        $user = User::factory()->create([
            'daily_login_streak' => 6,
            'last_login_date' => Carbon::today()->subDays(3),
        ]);

        $this->assertSame(1, $streaks->recordLogin($user));
    }
}
