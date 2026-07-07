<?php

namespace Tests\Feature;

use App\Livewire\Achievements as AchievementsPage;
use App\Livewire\Leaderboard;
use App\Models\User;
use App\Services\Progression\AchievementService;
use Database\Seeders\AchievementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProgressionUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_leaderboard_ranks_by_xp_and_excludes_guests(): void
    {
        $this->actingAs($me = User::factory()->create(['name' => 'Me', 'xp' => 500]));
        User::factory()->create(['name' => 'TopPlayer', 'xp' => 1000]);
        User::factory()->create(['name' => 'LowPlayer', 'xp' => 10]);
        User::factory()->create(['name' => 'AiBot', 'xp' => 9999, 'is_guest' => true]);

        Livewire::test(Leaderboard::class)
            ->assertOk()
            ->assertSeeInOrder(['TopPlayer', 'Me', 'LowPlayer'])
            ->assertDontSee('AiBot')   // guests excluded
            ->assertSee('#2');          // my position (behind TopPlayer only)
    }

    public function test_leaderboard_requires_authentication(): void
    {
        $this->get(route('leaderboard'))->assertRedirect(route('login'));
    }

    public function test_achievements_page_shows_locked_and_unlocked(): void
    {
        $this->seed(AchievementSeeder::class);
        $this->actingAs($me = User::factory()->create(['wins' => 1, 'total_matches' => 1]));

        // Unlock the ones the user now qualifies for.
        app(AchievementService::class)->evaluate($me);

        Livewire::test(AchievementsPage::class)
            ->assertOk()
            ->assertSee('Prestasi')
            ->assertSee('Kemenangan Perdana') // unlocked (wins >= 1)
            ->assertSee('Cendekiawan')        // still locked (correct_answers >= 100)
            ->assertSee('Terbuka ✓');
    }
}
