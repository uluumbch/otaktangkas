<?php

namespace Database\Seeders;

use App\Models\Achievement;
use Illuminate\Database\Seeder;

class AchievementSeeder extends Seeder
{
    public function run(): void
    {
        $achievements = [
            ['name' => 'Langkah Pertama', 'description' => 'Selesaikan pertandingan pertamamu.', 'rarity' => 'common', 'criteria' => ['type' => 'total_matches', 'value' => 1], 'xp_reward' => 20, 'coins_reward' => 10],
            ['name' => 'Kemenangan Perdana', 'description' => 'Menangkan pertandingan pertamamu.', 'rarity' => 'common', 'criteria' => ['type' => 'wins', 'value' => 1], 'xp_reward' => 30, 'coins_reward' => 15],
            ['name' => 'Juara Beruntun', 'description' => 'Raih 5 kemenangan beruntun.', 'rarity' => 'rare', 'criteria' => ['type' => 'win_streak', 'value' => 5], 'xp_reward' => 100, 'coins_reward' => 50],
            ['name' => 'Cendekiawan', 'description' => 'Jawab 100 pertanyaan dengan benar.', 'rarity' => 'epic', 'criteria' => ['type' => 'correct_answers', 'value' => 100], 'xp_reward' => 250, 'coins_reward' => 120],
            ['name' => 'Rajin Belajar', 'description' => 'Login 7 hari berturut-turut.', 'rarity' => 'rare', 'criteria' => ['type' => 'daily_login_streak', 'value' => 7], 'xp_reward' => 80, 'coins_reward' => 40],
        ];

        foreach ($achievements as $order => $achievement) {
            Achievement::updateOrCreate(
                ['slug' => \Illuminate\Support\Str::slug($achievement['name'])],
                [...$achievement, 'order' => $order, 'is_active' => true],
            );
        }
    }
}
