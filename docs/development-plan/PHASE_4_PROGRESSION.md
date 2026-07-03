# Phase 4: Progression System

## Overview
Complete progression and reward system for OtakTangkas including XP, levels, coins, achievements, leaderboards, streaks, and ranks. Designed to maximize player engagement and retention.

## Table of Contents
- [XP and Leveling System](#xp-and-leveling-system)
- [Coins and Virtual Currency](#coins-and-virtual-currency)
- [Achievement System](#achievement-system)
- [Leaderboard Implementation](#leaderboard-implementation)
- [Streak Tracking](#streak-tracking)
- [User Statistics](#user-statistics)
- [Rank System](#rank-system)
- [Progression Service](#progression-service)
- [Reward System](#reward-system)

---

## XP and Leveling System

### XP Formula

```php
<?php
// app/Services/XPService.php

namespace App\Services;

class XPService
{
    /**
     * Calculate XP required for next level
     * Formula: 100 * level^1.5
     */
    public function getXPForLevel(int $level): int
    {
        return (int) (100 * pow($level, 1.5));
    }

    /**
     * Calculate cumulative XP required to reach a level
     */
    public function getCumulativeXP(int $level): int
    {
        $total = 0;
        for ($i = 1; $i < $level; $i++) {
            $total += $this->getXPForLevel($i);
        }
        return $total;
    }

    /**
     * Calculate level from total XP
     */
    public function calculateLevel(int $totalXP): int
    {
        $level = 1;
        $xpNeeded = 0;

        while ($xpNeeded <= $totalXP) {
            $level++;
            $xpNeeded += $this->getXPForLevel($level - 1);
        }

        return $level - 1;
    }

    /**
     * Get XP progress percentage for current level
     */
    public function getXPProgress(int $currentXP, int $level): float
    {
        $currentLevelXP = $this->getCumulativeXP($level);
        $nextLevelXP = $this->getCumulativeXP($level + 1);
        $xpInCurrentLevel = $currentXP - $currentLevelXP;
        $xpNeededForLevel = $nextLevelXP - $currentLevelXP;

        return ($xpInCurrentLevel / $xpNeededForLevel) * 100;
    }
}
```

### XP Rewards Table

| Action | Base XP | Bonus Conditions |
|--------|---------|-----------------|
| Win Game | 50 XP | +25 XP for perfect game (no wrong answers) |
| Answer Question Correctly | 10 XP | +5 XP for hard questions |
| Daily Login | 20 XP | +10 XP for 7-day streak |
| Complete Daily Puzzle | 100 XP | +50 XP for top 10 ranking |
| Win Streak (3 games) | 50 XP | - |
| Win Streak (5 games) | 100 XP | - |
| Win Streak (10 games) | 250 XP | - |
| Complete Achievement | Varies | 50-500 XP |
| Level Up | 0 XP | Bonus coins |

### Level Progression Table

```
Level 1:  0 XP
Level 2:  100 XP (100 XP needed)
Level 3:  282 XP (182 XP needed)
Level 4:  520 XP (238 XP needed)
Level 5:  820 XP (300 XP needed)
Level 10: 3,162 XP
Level 20: 18,385 XP
Level 50: 176,776 XP
Level 100: 1,000,000 XP
```

### User Model Extension

```php
<?php
// app/Models/User.php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $fillable = [
        'name', 'email', 'password', 'xp', 'level', 'coins',
        'current_win_streak', 'best_win_streak', 'last_login_date',
        'daily_login_streak', 'rank',
    ];

    protected $casts = [
        'last_login_date' => 'date',
    ];

    /**
     * Award XP to user and check for level up
     */
    public function awardXP(int $amount, string $reason = null): array
    {
        $xpService = app(XPService::class);
        $oldLevel = $this->level;
        
        $this->xp += $amount;
        $newLevel = $xpService->calculateLevel($this->xp);
        
        $leveledUp = false;
        $coinsAwarded = 0;

        if ($newLevel > $oldLevel) {
            $leveledUp = true;
            $this->level = $newLevel;
            
            // Award coins for leveling up
            $coinsAwarded = 100 * $newLevel;
            $this->coins += $coinsAwarded;
            
            // Check for rank promotion
            $this->updateRank();
        }

        $this->save();

        // Log XP transaction
        $this->xpTransactions()->create([
            'amount' => $amount,
            'reason' => $reason,
            'balance_after' => $this->xp,
        ]);

        return [
            'leveled_up' => $leveledUp,
            'old_level' => $oldLevel,
            'new_level' => $this->level,
            'xp_awarded' => $amount,
            'coins_awarded' => $coinsAwarded,
        ];
    }

    /**
     * Get XP needed for next level
     */
    public function getNextLevelXPAttribute(): int
    {
        $xpService = app(XPService::class);
        return $xpService->getXPForLevel($this->level + 1);
    }

    /**
     * Get XP progress percentage
     */
    public function getXPPercentageAttribute(): float
    {
        $xpService = app(XPService::class);
        return $xpService->getXPProgress($this->xp, $this->level);
    }

    public function xpTransactions()
    {
        return $this->hasMany(XPTransaction::class);
    }
}
```

### XP Transaction Model

```php
<?php
// app/Models/XPTransaction.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class XPTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'reason',
        'balance_after',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

### Migration

```php
<?php
// database/migrations/2024_xx_xx_create_xp_transactions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('xp_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('amount');
            $table->string('reason')->nullable();
            $table->integer('balance_after');
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('xp_transactions');
    }
};
```

---

## Coins and Virtual Currency

### Coin Economy

**Earning Coins:**
- Win Game: 50 coins
- Daily Login: 25 coins
- Complete Daily Puzzle: 100 coins
- Level Up: 100 × level coins
- Watch Ad: 10 coins (max 10 per day)
- Achievement: Varies (50-500 coins)

**Spending Coins:**
- Skip Question: 20 coins
- Extra Life (future): 50 coins
- Cosmetic Items (future): 100-1000 coins
- Power-ups (future): 30-100 coins

### Coin Service

```php
<?php
// app/Services/CoinService.php

namespace App\Services;

use App\Models\User;
use App\Models\CoinTransaction;

class CoinService
{
    /**
     * Award coins to user
     */
    public function award(User $user, int $amount, string $reason): CoinTransaction
    {
        $user->coins += $amount;
        $user->save();

        return CoinTransaction::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'type' => 'earned',
            'reason' => $reason,
            'balance_after' => $user->coins,
        ]);
    }

    /**
     * Deduct coins from user
     */
    public function deduct(User $user, int $amount, string $reason): ?CoinTransaction
    {
        if ($user->coins < $amount) {
            return null; // Insufficient funds
        }

        $user->coins -= $amount;
        $user->save();

        return CoinTransaction::create([
            'user_id' => $user->id,
            'amount' => -$amount,
            'type' => 'spent',
            'reason' => $reason,
            'balance_after' => $user->coins,
        ]);
    }

    /**
     * Check if user can afford something
     */
    public function canAfford(User $user, int $amount): bool
    {
        return $user->coins >= $amount;
    }

    /**
     * Get user's coin history
     */
    public function getHistory(User $user, int $limit = 50)
    {
        return CoinTransaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get earning statistics
     */
    public function getStats(User $user): array
    {
        $transactions = CoinTransaction::where('user_id', $user->id);

        return [
            'total_earned' => $transactions->where('type', 'earned')->sum('amount'),
            'total_spent' => abs($transactions->where('type', 'spent')->sum('amount')),
            'current_balance' => $user->coins,
        ];
    }
}
```

### Coin Transaction Model

```php
<?php
// app/Models/CoinTransaction.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoinTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'type',
        'reason',
        'balance_after',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getAmountFormattedAttribute(): string
    {
        $prefix = $this->amount > 0 ? '+' : '';
        return $prefix . number_format($this->amount);
    }
}
```

---

## Achievement System

### Achievement Definitions

```php
<?php
// config/achievements.php

return [
    'first_win' => [
        'name' => 'Kemenangan Pertama',
        'description' => 'Menangkan game pertama kamu',
        'icon' => '🏆',
        'xp_reward' => 50,
        'coin_reward' => 100,
        'condition' => 'wins >= 1',
    ],
    'win_streak_3' => [
        'name' => 'Sedang Panas',
        'description' => 'Menang 3 game berturut-turut',
        'icon' => '🔥',
        'xp_reward' => 100,
        'coin_reward' => 150,
        'condition' => 'current_win_streak >= 3',
    ],
    'win_streak_10' => [
        'name' => 'Tak Terkalahkan',
        'description' => 'Menang 10 game berturut-turut',
        'icon' => '⚡',
        'xp_reward' => 500,
        'coin_reward' => 500,
        'condition' => 'current_win_streak >= 10',
    ],
    'reach_level_10' => [
        'name' => 'Veteran',
        'description' => 'Capai level 10',
        'icon' => '🎖️',
        'xp_reward' => 200,
        'coin_reward' => 300,
        'condition' => 'level >= 10',
    ],
    'reach_level_25' => [
        'name' => 'Master',
        'description' => 'Capai level 25',
        'icon' => '👑',
        'xp_reward' => 500,
        'coin_reward' => 750,
        'condition' => 'level >= 25',
    ],
    'reach_level_50' => [
        'name' => 'Legend',
        'description' => 'Capai level 50',
        'icon' => '💎',
        'xp_reward' => 1000,
        'coin_reward' => 1500,
        'condition' => 'level >= 50',
    ],
    'play_100_games' => [
        'name' => 'Rajin Bermain',
        'description' => 'Mainkan 100 game',
        'icon' => '🎮',
        'xp_reward' => 300,
        'coin_reward' => 400,
        'condition' => 'total_games >= 100',
    ],
    'win_50_games' => [
        'name' => 'Champion',
        'description' => 'Menangkan 50 game',
        'icon' => '🏅',
        'xp_reward' => 400,
        'coin_reward' => 600,
        'condition' => 'wins >= 50',
    ],
    'daily_login_7' => [
        'name' => 'Setia',
        'description' => 'Login 7 hari berturut-turut',
        'icon' => '📅',
        'xp_reward' => 150,
        'coin_reward' => 200,
        'condition' => 'daily_login_streak >= 7',
    ],
    'daily_login_30' => [
        'name' => 'Dedikasi Tinggi',
        'description' => 'Login 30 hari berturut-turut',
        'icon' => '⭐',
        'xp_reward' => 500,
        'coin_reward' => 1000,
        'condition' => 'daily_login_streak >= 30',
    ],
    'perfect_game' => [
        'name' => 'Sempurna',
        'description' => 'Menang tanpa salah jawab',
        'icon' => '💯',
        'xp_reward' => 100,
        'coin_reward' => 150,
        'condition' => 'perfect_games >= 1',
    ],
    'answer_1000_questions' => [
        'name' => 'Kutu Buku',
        'description' => 'Jawab 1000 pertanyaan',
        'icon' => '📚',
        'xp_reward' => 300,
        'coin_reward' => 500,
        'condition' => 'questions_answered >= 1000',
    ],
    'daily_puzzle_master' => [
        'name' => 'Puzzle Master',
        'description' => 'Selesaikan 30 daily puzzle',
        'icon' => '🧩',
        'xp_reward' => 400,
        'coin_reward' => 600,
        'condition' => 'daily_puzzles_completed >= 30',
    ],
];
```

### Achievement Model

```php
<?php
// app/Models/Achievement.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Achievement extends Model
{
    protected $fillable = [
        'key',
        'name',
        'description',
        'icon',
        'xp_reward',
        'coin_reward',
        'condition',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_achievements')
            ->withTimestamps()
            ->withPivot('progress');
    }
}
```

### Achievement Service

```php
<?php
// app/Services/AchievementService.php

namespace App\Services;

use App\Models\User;
use App\Models\Achievement;
use Illuminate\Support\Facades\DB;

class AchievementService
{
    /**
     * Check and award achievements for user
     */
    public function checkAchievements(User $user): array
    {
        $achievements = config('achievements');
        $awarded = [];

        foreach ($achievements as $key => $config) {
            // Check if already unlocked
            if ($user->achievements()->where('key', $key)->exists()) {
                continue;
            }

            // Check condition
            if ($this->checkCondition($user, $config['condition'])) {
                $awarded[] = $this->awardAchievement($user, $key, $config);
            }
        }

        return $awarded;
    }

    /**
     * Award specific achievement
     */
    protected function awardAchievement(User $user, string $key, array $config): array
    {
        // Create or get achievement
        $achievement = Achievement::firstOrCreate(
            ['key' => $key],
            [
                'name' => $config['name'],
                'description' => $config['description'],
                'icon' => $config['icon'],
                'xp_reward' => $config['xp_reward'],
                'coin_reward' => $config['coin_reward'],
                'condition' => $config['condition'],
            ]
        );

        // Attach to user
        $user->achievements()->attach($achievement->id, [
            'progress' => 100,
            'unlocked_at' => now(),
        ]);

        // Award rewards
        $user->awardXP($config['xp_reward'], "Achievement: {$config['name']}");
        
        $coinService = app(CoinService::class);
        $coinService->award($user, $config['coin_reward'], "Achievement: {$config['name']}");

        return [
            'achievement' => $achievement,
            'xp_reward' => $config['xp_reward'],
            'coin_reward' => $config['coin_reward'],
        ];
    }

    /**
     * Check if condition is met
     */
    protected function checkCondition(User $user, string $condition): bool
    {
        // Parse condition (e.g., "wins >= 1")
        preg_match('/(\w+)\s*(>=|<=|>|<|==)\s*(\d+)/', $condition, $matches);
        
        if (count($matches) !== 4) {
            return false;
        }

        $field = $matches[1];
        $operator = $matches[2];
        $value = (int) $matches[3];

        // Get user stats
        $stats = $this->getUserStats($user);
        $userValue = $stats[$field] ?? 0;

        return match($operator) {
            '>=' => $userValue >= $value,
            '<=' => $userValue <= $value,
            '>' => $userValue > $value,
            '<' => $userValue < $value,
            '==' => $userValue == $value,
            default => false,
        };
    }

    /**
     * Get user statistics for achievement checking
     */
    protected function getUserStats(User $user): array
    {
        return [
            'wins' => $user->wins,
            'losses' => $user->losses,
            'current_win_streak' => $user->current_win_streak,
            'best_win_streak' => $user->best_win_streak,
            'level' => $user->level,
            'total_games' => $user->wins + $user->losses,
            'daily_login_streak' => $user->daily_login_streak,
            'perfect_games' => $user->perfect_games,
            'questions_answered' => $user->questions_answered,
            'daily_puzzles_completed' => $user->daily_puzzles_completed,
        ];
    }

    /**
     * Get user's achievement progress
     */
    public function getProgress(User $user): array
    {
        $achievements = config('achievements');
        $unlocked = $user->achievements()->pluck('key')->toArray();
        
        $progress = [];
        foreach ($achievements as $key => $config) {
            $progress[] = [
                'key' => $key,
                'name' => $config['name'],
                'description' => $config['description'],
                'icon' => $config['icon'],
                'unlocked' => in_array($key, $unlocked),
                'xp_reward' => $config['xp_reward'],
                'coin_reward' => $config['coin_reward'],
            ];
        }

        return $progress;
    }
}
```

---

## Leaderboard Implementation

### Leaderboard Service

```php
<?php
// app/Services/LeaderboardService.php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LeaderboardService
{
    /**
     * Get daily leaderboard (top XP earned today)
     */
    public function getDailyLeaderboard(int $limit = 50): array
    {
        return Cache::remember('leaderboard:daily', 300, function () use ($limit) {
            $today = now()->startOfDay();
            
            return DB::table('xp_transactions')
                ->select('user_id', DB::raw('SUM(amount) as daily_xp'))
                ->where('created_at', '>=', $today)
                ->groupBy('user_id')
                ->orderByDesc('daily_xp')
                ->limit($limit)
                ->get()
                ->map(function ($item) {
                    $user = User::find($item->user_id);
                    return [
                        'user' => $user,
                        'score' => $item->daily_xp,
                        'metric' => 'XP Hari Ini',
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Get weekly leaderboard (top XP earned this week)
     */
    public function getWeeklyLeaderboard(int $limit = 50): array
    {
        return Cache::remember('leaderboard:weekly', 600, function () use ($limit) {
            $weekStart = now()->startOfWeek();
            
            return DB::table('xp_transactions')
                ->select('user_id', DB::raw('SUM(amount) as weekly_xp'))
                ->where('created_at', '>=', $weekStart)
                ->groupBy('user_id')
                ->orderByDesc('weekly_xp')
                ->limit($limit)
                ->get()
                ->map(function ($item) {
                    $user = User::find($item->user_id);
                    return [
                        'user' => $user,
                        'score' => $item->weekly_xp,
                        'metric' => 'XP Minggu Ini',
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Get all-time leaderboard (total XP)
     */
    public function getAllTimeLeaderboard(int $limit = 100): array
    {
        return Cache::remember('leaderboard:all-time', 3600, function () use ($limit) {
            return User::select('id', 'name', 'avatar', 'xp', 'level', 'rank')
                ->orderByDesc('xp')
                ->limit($limit)
                ->get()
                ->map(function ($user, $index) {
                    return [
                        'user' => $user,
                        'rank' => $index + 1,
                        'score' => $user->xp,
                        'metric' => 'Total XP',
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Get user's ranking
     */
    public function getUserRank(User $user): array
    {
        $dailyRank = $this->getUserDailyRank($user);
        $weeklyRank = $this->getUserWeeklyRank($user);
        $allTimeRank = $this->getUserAllTimeRank($user);

        return [
            'daily' => $dailyRank,
            'weekly' => $weeklyRank,
            'all_time' => $allTimeRank,
        ];
    }

    protected function getUserDailyRank(User $user): ?int
    {
        $today = now()->startOfDay();
        
        $dailyXP = DB::table('xp_transactions')
            ->where('user_id', $user->id)
            ->where('created_at', '>=', $today)
            ->sum('amount');

        $rank = DB::table('xp_transactions')
            ->select('user_id', DB::raw('SUM(amount) as daily_xp'))
            ->where('created_at', '>=', $today)
            ->groupBy('user_id')
            ->havingRaw('daily_xp > ?', [$dailyXP])
            ->count();

        return $rank + 1;
    }

    protected function getUserWeeklyRank(User $user): ?int
    {
        $weekStart = now()->startOfWeek();
        
        $weeklyXP = DB::table('xp_transactions')
            ->where('user_id', $user->id)
            ->where('created_at', '>=', $weekStart)
            ->sum('amount');

        $rank = DB::table('xp_transactions')
            ->select('user_id', DB::raw('SUM(amount) as weekly_xp'))
            ->where('created_at', '>=', $weekStart)
            ->groupBy('user_id')
            ->havingRaw('weekly_xp > ?', [$weeklyXP])
            ->count();

        return $rank + 1;
    }

    protected function getUserAllTimeRank(User $user): int
    {
        return User::where('xp', '>', $user->xp)->count() + 1;
    }

    /**
     * Clear leaderboard cache
     */
    public function clearCache(): void
    {
        Cache::forget('leaderboard:daily');
        Cache::forget('leaderboard:weekly');
        Cache::forget('leaderboard:all-time');
    }
}
```

### Leaderboard View

```blade
<!-- resources/views/leaderboard.blade.php -->
<x-app-layout>
    <div class="max-w-4xl mx-auto">
        
        <h1 class="text-3xl font-display font-bold text-gray-900 mb-6">Leaderboard 🏆</h1>

        <!-- Tabs -->
        <div x-data="{ tab: 'daily' }" class="bg-white rounded-xl shadow-card overflow-hidden">
            
            <!-- Tab Navigation -->
            <div class="flex border-b border-gray-200">
                <button @click="tab = 'daily'" 
                        :class="tab === 'daily' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-600'"
                        class="flex-1 px-6 py-4 border-b-2 font-semibold transition-colors">
                    Harian
                </button>
                <button @click="tab = 'weekly'" 
                        :class="tab === 'weekly' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-600'"
                        class="flex-1 px-6 py-4 border-b-2 font-semibold transition-colors">
                    Mingguan
                </button>
                <button @click="tab = 'alltime'" 
                        :class="tab === 'alltime' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-600'"
                        class="flex-1 px-6 py-4 border-b-2 font-semibold transition-colors">
                    Sepanjang Masa
                </button>
            </div>

            <!-- Daily Leaderboard -->
            <div x-show="tab === 'daily'" class="p-6">
                @foreach($dailyLeaderboard as $index => $entry)
                <div class="flex items-center justify-between p-4 mb-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <div class="flex items-center space-x-4">
                        <!-- Rank -->
                        <div class="w-8 text-center">
                            @if($index === 0)
                                <span class="text-2xl">🥇</span>
                            @elseif($index === 1)
                                <span class="text-2xl">🥈</span>
                            @elseif($index === 2)
                                <span class="text-2xl">🥉</span>
                            @else
                                <span class="text-lg font-bold text-gray-600">#{{ $index + 1 }}</span>
                            @endif
                        </div>

                        <!-- User Info -->
                        <img src="{{ $entry['user']->avatar_url }}" alt="{{ $entry['user']->name }}" 
                             class="w-12 h-12 rounded-full">
                        <div>
                            <p class="font-semibold text-gray-900">{{ $entry['user']->name }}</p>
                            <p class="text-sm text-gray-600">Level {{ $entry['user']->level }}</p>
                        </div>
                    </div>

                    <!-- Score -->
                    <div class="text-right">
                        <p class="text-xl font-bold text-primary-600">{{ number_format($entry['score']) }}</p>
                        <p class="text-sm text-gray-600">XP</p>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Weekly Leaderboard -->
            <div x-show="tab === 'weekly'" class="p-6">
                @foreach($weeklyLeaderboard as $index => $entry)
                <div class="flex items-center justify-between p-4 mb-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <div class="flex items-center space-x-4">
                        <div class="w-8 text-center">
                            @if($index === 0)
                                <span class="text-2xl">🥇</span>
                            @elseif($index === 1)
                                <span class="text-2xl">🥈</span>
                            @elseif($index === 2)
                                <span class="text-2xl">🥉</span>
                            @else
                                <span class="text-lg font-bold text-gray-600">#{{ $index + 1 }}</span>
                            @endif
                        </div>
                        <img src="{{ $entry['user']->avatar_url }}" alt="{{ $entry['user']->name }}" 
                             class="w-12 h-12 rounded-full">
                        <div>
                            <p class="font-semibold text-gray-900">{{ $entry['user']->name }}</p>
                            <p class="text-sm text-gray-600">Level {{ $entry['user']->level }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-xl font-bold text-primary-600">{{ number_format($entry['score']) }}</p>
                        <p class="text-sm text-gray-600">XP</p>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- All-Time Leaderboard -->
            <div x-show="tab === 'alltime'" class="p-6">
                @foreach($allTimeLeaderboard as $entry)
                <div class="flex items-center justify-between p-4 mb-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <div class="flex items-center space-x-4">
                        <div class="w-8 text-center">
                            @if($entry['rank'] === 1)
                                <span class="text-2xl">🥇</span>
                            @elseif($entry['rank'] === 2)
                                <span class="text-2xl">🥈</span>
                            @elseif($entry['rank'] === 3)
                                <span class="text-2xl">🥉</span>
                            @else
                                <span class="text-lg font-bold text-gray-600">#{{ $entry['rank'] }}</span>
                            @endif
                        </div>
                        <img src="{{ $entry['user']->avatar_url }}" alt="{{ $entry['user']->name }}" 
                             class="w-12 h-12 rounded-full">
                        <div>
                            <p class="font-semibold text-gray-900">{{ $entry['user']->name }}</p>
                            <p class="text-sm text-gray-600">{{ $entry['user']->rank }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-xl font-bold text-primary-600">{{ number_format($entry['score']) }}</p>
                        <p class="text-sm text-gray-600">Total XP</p>
                    </div>
                </div>
                @endforeach
            </div>

        </div>

        <!-- Your Ranking -->
        <div class="mt-6 bg-linear-to-r from-primary-500 to-secondary-600 rounded-xl p-6 text-white">
            <h3 class="text-lg font-bold mb-4">Peringkat Kamu</h3>
            <div class="grid grid-cols-3 gap-4">
                <div class="text-center">
                    <p class="text-3xl font-bold">{{ $userRank['daily'] }}</p>
                    <p class="text-sm text-primary-100">Harian</p>
                </div>
                <div class="text-center">
                    <p class="text-3xl font-bold">{{ $userRank['weekly'] }}</p>
                    <p class="text-sm text-primary-100">Mingguan</p>
                </div>
                <div class="text-center">
                    <p class="text-3xl font-bold">{{ $userRank['all_time'] }}</p>
                    <p class="text-sm text-primary-100">Sepanjang Masa</p>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
```

---

## Streak Tracking

### Streak Service

```php
<?php
// app/Services/StreakService.php

namespace App\Services;

use App\Models\User;

class StreakService
{
    /**
     * Update daily login streak
     */
    public function updateLoginStreak(User $user): array
    {
        $today = now()->startOfDay();
        $lastLogin = $user->last_login_date ? $user->last_login_date->startOfDay() : null;

        // First login ever
        if (!$lastLogin) {
            $user->daily_login_streak = 1;
            $user->last_login_date = $today;
            $user->save();

            return [
                'streak' => 1,
                'is_new_day' => true,
                'bonus_xp' => 20,
            ];
        }

        // Same day login
        if ($lastLogin->equalTo($today)) {
            return [
                'streak' => $user->daily_login_streak,
                'is_new_day' => false,
                'bonus_xp' => 0,
            ];
        }

        // Yesterday login (continue streak)
        if ($lastLogin->addDay()->equalTo($today)) {
            $user->daily_login_streak += 1;
            $user->last_login_date = $today;
            
            // Bonus XP for streak milestones
            $bonusXP = $this->getStreakBonusXP($user->daily_login_streak);
            if ($bonusXP > 0) {
                $user->awardXP($bonusXP, "Login streak: {$user->daily_login_streak} hari");
            }
            
            $user->save();

            return [
                'streak' => $user->daily_login_streak,
                'is_new_day' => true,
                'bonus_xp' => 20 + $bonusXP,
                'milestone' => $bonusXP > 0,
            ];
        }

        // Streak broken
        $user->daily_login_streak = 1;
        $user->last_login_date = $today;
        $user->save();

        return [
            'streak' => 1,
            'is_new_day' => true,
            'bonus_xp' => 20,
            'streak_broken' => true,
        ];
    }

    /**
     * Get bonus XP for streak milestones
     */
    protected function getStreakBonusXP(int $streak): int
    {
        return match(true) {
            $streak === 7 => 100,
            $streak === 14 => 200,
            $streak === 30 => 500,
            $streak === 60 => 1000,
            $streak === 90 => 1500,
            $streak % 100 === 0 => 2000,
            default => 0,
        };
    }

    /**
     * Update win streak
     */
    public function updateWinStreak(User $user, bool $won): array
    {
        if ($won) {
            $user->current_win_streak += 1;
            
            if ($user->current_win_streak > $user->best_win_streak) {
                $user->best_win_streak = $user->current_win_streak;
            }

            // Bonus XP for win streak milestones
            $bonusXP = $this->getWinStreakBonusXP($user->current_win_streak);
            if ($bonusXP > 0) {
                $user->awardXP($bonusXP, "Win streak: {$user->current_win_streak} kemenangan");
            }

            $user->save();

            return [
                'current_streak' => $user->current_win_streak,
                'best_streak' => $user->best_win_streak,
                'bonus_xp' => $bonusXP,
                'milestone' => $bonusXP > 0,
            ];
        } else {
            $oldStreak = $user->current_win_streak;
            $user->current_win_streak = 0;
            $user->save();

            return [
                'current_streak' => 0,
                'best_streak' => $user->best_win_streak,
                'previous_streak' => $oldStreak,
                'streak_broken' => $oldStreak > 0,
            ];
        }
    }

    /**
     * Get bonus XP for win streak milestones
     */
    protected function getWinStreakBonusXP(int $streak): int
    {
        return match(true) {
            $streak === 3 => 50,
            $streak === 5 => 100,
            $streak === 10 => 250,
            $streak === 20 => 500,
            $streak === 50 => 1000,
            $streak % 100 === 0 => 2000,
            default => 0,
        };
    }
}
```

---

## User Statistics

### Statistics Tracking

```php
<?php
// Add to User model

class User extends Authenticatable
{
    protected $fillable = [
        // ... existing fields
        'wins',
        'losses',
        'draws',
        'perfect_games',
        'questions_answered',
        'questions_correct',
        'daily_puzzles_completed',
        'current_win_streak',
        'best_win_streak',
        'daily_login_streak',
    ];

    /**
     * Get win rate percentage
     */
    public function getWinRateAttribute(): float
    {
        $total = $this->wins + $this->losses + $this->draws;
        return $total > 0 ? round(($this->wins / $total) * 100, 2) : 0;
    }

    /**
     * Get accuracy percentage
     */
    public function getAccuracyAttribute(): float
    {
        return $this->questions_answered > 0 
            ? round(($this->questions_correct / $this->questions_answered) * 100, 2) 
            : 0;
    }

    /**
     * Record game result
     */
    public function recordGameResult(string $result, bool $perfect = false): void
    {
        match($result) {
            'win' => $this->wins++,
            'loss' => $this->losses++,
            'draw' => $this->draws++,
        };

        if ($perfect && $result === 'win') {
            $this->perfect_games++;
        }

        $this->save();

        // Update streak
        $streakService = app(StreakService::class);
        $streakService->updateWinStreak($this, $result === 'win');
    }

    /**
     * Record question answer
     */
    public function recordAnswer(bool $correct): void
    {
        $this->questions_answered++;
        if ($correct) {
            $this->questions_correct++;
        }
        $this->save();
    }
}
```

---

## Rank System

### Rank Configuration

```php
<?php
// config/ranks.php

return [
    'bronze' => [
        'name' => 'Bronze',
        'icon' => '🥉',
        'color' => '#CD7F32',
        'min_level' => 1,
        'max_level' => 9,
    ],
    'silver' => [
        'name' => 'Silver',
        'icon' => '🥈',
        'color' => '#C0C0C0',
        'min_level' => 10,
        'max_level' => 19,
    ],
    'gold' => [
        'name' => 'Gold',
        'icon' => '🥇',
        'color' => '#FFD700',
        'min_level' => 20,
        'max_level' => 34,
    ],
    'platinum' => [
        'name' => 'Platinum',
        'icon' => '💎',
        'color' => '#E5E4E2',
        'min_level' => 35,
        'max_level' => 49,
    ],
    'diamond' => [
        'name' => 'Diamond',
        'icon' => '💠',
        'color' => '#B9F2FF',
        'min_level' => 50,
        'max_level' => 999,
    ],
];
```

### Rank Service

```php
<?php
// app/Services/RankService.php

namespace App\Services;

use App\Models\User;

class RankService
{
    /**
     * Get rank for level
     */
    public function getRankForLevel(int $level): array
    {
        $ranks = config('ranks');

        foreach ($ranks as $key => $rank) {
            if ($level >= $rank['min_level'] && $level <= $rank['max_level']) {
                return array_merge($rank, ['key' => $key]);
            }
        }

        return $ranks['bronze'];
    }

    /**
     * Update user rank
     */
    public function updateUserRank(User $user): ?array
    {
        $currentRank = $user->rank;
        $newRank = $this->getRankForLevel($user->level);

        if ($currentRank !== $newRank['key']) {
            $user->rank = $newRank['key'];
            $user->save();

            return [
                'promoted' => true,
                'old_rank' => $currentRank,
                'new_rank' => $newRank,
            ];
        }

        return null;
    }
}
```

---

## Progression Service

### Complete Progression Service

```php
<?php
// app/Services/ProgressionService.php

namespace App\Services;

use App\Models\User;
use App\Models\Game;

class ProgressionService
{
    public function __construct(
        protected XPService $xpService,
        protected CoinService $coinService,
        protected AchievementService $achievementService,
        protected StreakService $streakService,
        protected RankService $rankService,
    ) {}

    /**
     * Process game completion and award rewards
     */
    public function processGameCompletion(Game $game, User $user): array
    {
        $isWinner = $game->winner_id === $user->id;
        $isDraw = $game->winner_id === null;
        $isPerfect = $this->isPerfectGame($game, $user);

        $rewards = [
            'xp' => 0,
            'coins' => 0,
            'leveled_up' => false,
            'achievements' => [],
            'rank_up' => null,
        ];

        // Base rewards
        if ($isWinner) {
            $xpReward = 50;
            $coinReward = 50;

            if ($isPerfect) {
                $xpReward += 25;
                $coinReward += 25;
            }

            $xpResult = $user->awardXP($xpReward, 'Won game');
            $this->coinService->award($user, $coinReward, 'Won game');

            $rewards['xp'] = $xpReward;
            $rewards['coins'] = $coinReward;
            $rewards['leveled_up'] = $xpResult['leveled_up'];
            
            if ($xpResult['leveled_up']) {
                $rewards['coins'] += $xpResult['coins_awarded'];
            }

            // Record game result
            $user->recordGameResult('win', $isPerfect);
        } elseif ($isDraw) {
            $xpReward = 25;
            $coinReward = 25;

            $user->awardXP($xpReward, 'Draw game');
            $this->coinService->award($user, $coinReward, 'Draw game');

            $rewards['xp'] = $xpReward;
            $rewards['coins'] = $coinReward;

            $user->recordGameResult('draw');
        } else {
            // Loss - small consolation prize
            $xpReward = 10;
            $coinReward = 10;

            $user->awardXP($xpReward, 'Participated in game');
            $this->coinService->award($user, $coinReward, 'Participated in game');

            $rewards['xp'] = $xpReward;
            $rewards['coins'] = $coinReward;

            $user->recordGameResult('loss');
        }

        // Check for achievements
        $newAchievements = $this->achievementService->checkAchievements($user);
        $rewards['achievements'] = $newAchievements;

        // Check for rank promotion
        $rankPromotion = $this->rankService->updateUserRank($user);
        $rewards['rank_up'] = $rankPromotion;

        return $rewards;
    }

    /**
     * Check if user played a perfect game
     */
    protected function isPerfectGame(Game $game, User $user): bool
    {
        // Logic to check if user answered all questions correctly
        // This depends on how you store question answers in the game
        return false; // Placeholder
    }

    /**
     * Process daily login
     */
    public function processDailyLogin(User $user): array
    {
        $streakResult = $this->streakService->updateLoginStreak($user);

        if ($streakResult['is_new_day']) {
            $user->awardXP(20, 'Daily login');
            $this->coinService->award($user, 25, 'Daily login');
        }

        // Check achievements
        $this->achievementService->checkAchievements($user);

        return $streakResult;
    }
}
```

---

## Reward System

### Reward Notification Component

```blade
<!-- resources/views/components/reward-modal.blade.php -->
<div x-data="{ 
    show: false,
    rewards: {},
    init() {
        window.addEventListener('show-rewards', (e) => {
            this.rewards = e.detail;
            this.show = true;
        });
    }
}" 
    x-show="show"
    x-transition
    class="fixed inset-0 bg-black/75 flex items-center justify-center z-50 p-4">
    
    <div class="bg-white rounded-2xl max-w-md w-full p-8 text-center" @click.away="show = false">
        
        <!-- Victory Animation -->
        <div class="mb-6">
            <div class="w-24 h-24 bg-linear-to-br from-yellow-400 to-yellow-600 rounded-full flex items-center justify-center mx-auto animate-bounce">
                <i class="fas fa-trophy text-white text-5xl"></i>
            </div>
        </div>

        <h2 class="text-3xl font-display font-bold text-gray-900 mb-2">Selamat!</h2>
        <p class="text-gray-600 mb-6">Kamu mendapatkan reward:</p>

        <!-- Rewards List -->
        <div class="space-y-3 mb-6">
            <!-- XP Reward -->
            <div class="flex items-center justify-between p-4 bg-blue-50 rounded-lg">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-star text-blue-600 text-2xl"></i>
                    <span class="font-semibold text-gray-900">XP</span>
                </div>
                <span class="text-2xl font-bold text-blue-600" x-text="'+' + rewards.xp"></span>
            </div>

            <!-- Coins Reward -->
            <div class="flex items-center justify-between p-4 bg-yellow-50 rounded-lg">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-coins text-yellow-600 text-2xl"></i>
                    <span class="font-semibold text-gray-900">Koin</span>
                </div>
                <span class="text-2xl font-bold text-yellow-600" x-text="'+' + rewards.coins"></span>
            </div>

            <!-- Level Up -->
            <template x-if="rewards.leveled_up">
                <div class="p-4 bg-linear-to-r from-primary-500 to-secondary-600 rounded-lg text-white">
                    <p class="text-xl font-bold">🎉 LEVEL UP!</p>
                    <p class="text-lg">Level <span x-text="rewards.new_level"></span></p>
                </div>
            </template>

            <!-- Achievements -->
            <template x-if="rewards.achievements && rewards.achievements.length > 0">
                <div class="p-4 bg-purple-50 rounded-lg">
                    <p class="font-bold text-purple-900 mb-2">Pencapaian Baru:</p>
                    <template x-for="achievement in rewards.achievements">
                        <p class="text-sm" x-text="achievement.achievement.icon + ' ' + achievement.achievement.name"></p>
                    </template>
                </div>
            </template>
        </div>

        <button @click="show = false" 
                class="w-full bg-primary-500 hover:bg-primary-600 text-white font-bold py-3 rounded-lg transition-colors">
            Lanjutkan
        </button>
    </div>
</div>
```

---

## Completion Checklist

### Database Setup
- [ ] Create xp_transactions table
- [ ] Create coin_transactions table
- [ ] Create achievements table
- [ ] Create user_achievements pivot table
- [ ] Add progression fields to users table

### Service Implementation
- [ ] Implement XPService
- [ ] Implement CoinService
- [ ] Implement AchievementService
- [ ] Implement LeaderboardService
- [ ] Implement StreakService
- [ ] Implement RankService
- [ ] Implement ProgressionService

### Achievement Configuration
- [ ] Define all achievements in config
- [ ] Create achievement icons
- [ ] Set up reward values
- [ ] Implement condition checking

### Leaderboard Features
- [ ] Daily leaderboard
- [ ] Weekly leaderboard
- [ ] All-time leaderboard
- [ ] User ranking display
- [ ] Leaderboard caching

### Streak Features
- [ ] Daily login streak
- [ ] Win streak tracking
- [ ] Streak milestone rewards
- [ ] Streak broken notifications

### UI Components
- [ ] Reward modal
- [ ] Achievement cards
- [ ] Leaderboard views
- [ ] Progress bars
- [ ] Stat displays

### Testing
- [ ] Test XP calculations
- [ ] Test level up system
- [ ] Test coin transactions
- [ ] Test achievement unlocking
- [ ] Test leaderboard rankings
- [ ] Test streak tracking

---

## Related Documentation
- [PHASE_2_GAME_ENGINE.md](./PHASE_2_GAME_ENGINE.md)
- [PHASE_3_FRONTEND.md](./PHASE_3_FRONTEND.md)
- [PHASE_5_SOLO_MODES.md](./PHASE_5_SOLO_MODES.md)

---

**Last Updated:** July 2026
**Status:** Ready for Implementation
