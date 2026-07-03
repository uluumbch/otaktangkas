# 🏗️ Phase 1: Foundation

> **Duration**: 3-4 days  
> **Goal**: Build database schema, models, authentication, and admin panel

## 📋 Overview

Phase 1 establishes the data foundation for OtakTangkas. We'll create all database tables, Eloquent models, relationships, authentication system, guest user functionality, and the admin panel.

---

## 🗄️ Database Migrations

### Migration: Users Table (Enhanced)

**File**: `database/migrations/2024_01_01_000000_enhance_users_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Profile
            $table->string('username')->unique()->after('email');
            $table->string('avatar')->nullable()->after('username');
            $table->text('bio')->nullable()->after('avatar');
            
            // Progression
            $table->integer('xp')->default(0);
            $table->integer('level')->default(1);
            $table->integer('coins')->default(100); // Starting coins
            $table->string('rank')->default('bronze'); // bronze, silver, gold, platinum, diamond
            
            // Statistics
            $table->integer('total_matches')->default(0);
            $table->integer('wins')->default(0);
            $table->integer('losses')->default(0);
            $table->integer('draws')->default(0);
            $table->integer('win_streak')->default(0);
            $table->integer('best_win_streak')->default(0);
            $table->integer('questions_answered')->default(0);
            $table->integer('correct_answers')->default(0);
            
            // Streaks
            $table->integer('daily_login_streak')->default(0);
            $table->date('last_login_date')->nullable();
            
            // Premium
            $table->boolean('is_premium')->default(false);
            $table->timestamp('premium_until')->nullable();
            
            // Guest users
            $table->boolean('is_guest')->default(false);
            $table->timestamp('guest_expires_at')->nullable();
            
            // Settings
            $table->string('preferred_language')->default('id'); // id or en
            $table->json('settings')->nullable(); // JSON for various settings
            
            // Referral
            $table->string('referral_code')->unique()->nullable();
            $table->foreignId('referred_by_id')->nullable()->constrained('users');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'username', 'avatar', 'bio',
                'xp', 'level', 'coins', 'rank',
                'total_matches', 'wins', 'losses', 'draws',
                'win_streak', 'best_win_streak',
                'questions_answered', 'correct_answers',
                'daily_login_streak', 'last_login_date',
                'is_premium', 'premium_until',
                'is_guest', 'guest_expires_at',
                'preferred_language', 'settings',
                'referral_code', 'referred_by_id'
            ]);
        });
    }
};
```

### Migration: Categories Table

**File**: `database/migrations/2024_01_02_000000_create_categories_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Math, Science, Indonesian Culture, etc.
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable(); // Icon class or path
            $table->string('color')->default('#3B82F6'); // Hex color
            $table->integer('order')->default(0); // Display order
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
```

### Migration: Questions Table (Enhanced)

**File**: `database/migrations/2024_01_03_000000_enhance_questions_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->foreignId('category_id')->after('id')->constrained()->cascadeOnDelete();
            $table->enum('difficulty', ['easy', 'medium', 'hard'])->default('medium');
            $table->string('language')->default('id'); // id or en
            $table->integer('time_limit')->default(30); // seconds
            $table->integer('xp_reward')->default(10);
            $table->integer('coins_reward')->default(5);
            $table->boolean('is_active')->default(true);
            $table->integer('times_used')->default(0);
            $table->integer('times_correct')->default(0);
            $table->integer('times_incorrect')->default(0);
            $table->text('explanation')->nullable(); // Answer explanation
            $table->json('tags')->nullable(); // Additional tags
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn([
                'category_id', 'difficulty', 'language',
                'time_limit', 'xp_reward', 'coins_reward',
                'is_active', 'times_used', 'times_correct',
                'times_incorrect', 'explanation', 'tags'
            ]);
        });
    }
};
```

### Migration: Answers Table (No changes needed)

Existing structure is adequate.

### Migration: Matches Table (Enhanced)

**File**: `database/migrations/2024_01_04_000000_enhance_matches_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->string('match_code')->unique(); // For joining
            
            // Game type
            $table->enum('game_type', ['tic_tac_toe', 'memory_match'])->default('tic_tac_toe');
            $table->enum('mode', ['quick_play', 'invite', 'practice', 'daily_puzzle'])->default('quick_play');
            
            // Players
            $table->foreignId('player1_id')->constrained('users');
            $table->foreignId('player2_id')->nullable()->constrained('users');
            $table->string('player1_symbol')->default('X');
            $table->string('player2_symbol')->default('O');
            
            // Game state
            $table->enum('status', ['waiting', 'in_progress', 'completed', 'abandoned'])->default('waiting');
            $table->foreignId('current_turn_user_id')->nullable()->constrained('users');
            $table->json('board_state')->nullable(); // Game board JSON
            $table->foreignId('winner_id')->nullable()->constrained('users');
            $table->enum('result', ['player1_win', 'player2_win', 'draw', 'abandoned'])->nullable();
            
            // Questions
            $table->foreignId('current_question_id')->nullable()->constrained('questions');
            $table->json('question_history')->nullable(); // Track used questions
            
            // Timing
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->integer('turn_time_limit')->default(30); // seconds per turn
            $table->timestamp('turn_started_at')->nullable();
            
            // Rewards
            $table->integer('xp_awarded')->default(0);
            $table->integer('coins_awarded')->default(0);
            
            // Settings
            $table->foreignId('category_id')->nullable()->constrained();
            $table->enum('difficulty', ['easy', 'medium', 'hard', 'mixed'])->default('mixed');
            
            $table->timestamps();
            
            // Indexes
            $table->index('status');
            $table->index('player1_id');
            $table->index('player2_id');
            $table->index('mode');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
```

### Migration: Match Moves Table

**File**: `database/migrations/2024_01_05_000000_create_match_moves_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_moves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('question_id')->constrained();
            $table->foreignId('answer_id')->nullable()->constrained();
            
            // Move details
            $table->integer('move_number'); // 1, 2, 3, ...
            $table->string('position'); // For tic-tac-toe: "0,0", "1,2", etc.
            $table->boolean('is_correct');
            $table->integer('time_taken'); // seconds
            
            // Rewards
            $table->integer('xp_earned')->default(0);
            $table->integer('coins_earned')->default(0);
            
            $table->timestamps();
            
            // Indexes
            $table->index('match_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_moves');
    }
};
```

### Migration: Achievements Table

**File**: `database/migrations/2024_01_06_000000_create_achievements_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('achievements', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->string('icon')->nullable(); // Icon class or path
            $table->enum('rarity', ['common', 'rare', 'epic', 'legendary'])->default('common');
            
            // Unlock criteria (stored as JSON for flexibility)
            $table->json('criteria'); // e.g., {"type": "wins", "value": 10}
            
            // Rewards
            $table->integer('xp_reward')->default(0);
            $table->integer('coins_reward')->default(0);
            
            // Display
            $table->integer('order')->default(0);
            $table->boolean('is_hidden')->default(false); // Secret achievements
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('achievements');
    }
};
```

### Migration: User Achievements (Pivot)

**File**: `database/migrations/2024_01_07_000000_create_user_achievements_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('achievement_id')->constrained()->cascadeOnDelete();
            $table->integer('progress')->default(0); // For multi-step achievements
            $table->boolean('is_claimed')->default(false);
            $table->timestamp('unlocked_at');
            
            $table->unique(['user_id', 'achievement_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_achievements');
    }
};
```

### Migration: Daily Puzzles Table

**File**: `database/migrations/2024_01_08_000000_create_daily_puzzles_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_puzzles', function (Blueprint $table) {
            $table->id();
            $table->date('puzzle_date')->unique();
            $table->enum('game_type', ['tic_tac_toe'])->default('tic_tac_toe');
            $table->foreignId('category_id')->constrained();
            $table->enum('difficulty', ['easy', 'medium', 'hard'])->default('medium');
            $table->json('question_ids'); // Array of question IDs
            $table->json('puzzle_config')->nullable(); // Special puzzle configuration
            $table->integer('xp_reward')->default(50);
            $table->integer('coins_reward')->default(25);
            $table->integer('time_limit')->default(300); // 5 minutes
            $table->integer('attempts_count')->default(0);
            $table->integer('completed_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_puzzles');
    }
};
```

### Migration: Daily Puzzle Attempts Table

**File**: `database/migrations/2024_01_09_000000_create_daily_puzzle_attempts_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_puzzle_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_puzzle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_completed')->default(false);
            $table->integer('time_taken')->nullable(); // seconds
            $table->integer('moves_used')->default(0);
            $table->integer('correct_answers')->default(0);
            $table->integer('score')->default(0);
            $table->integer('xp_earned')->default(0);
            $table->integer('coins_earned')->default(0);
            $table->timestamps();
            
            $table->unique(['daily_puzzle_id', 'user_id']);
            $table->index('daily_puzzle_id');
            $table->index(['daily_puzzle_id', 'score']); // For rankings
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_puzzle_attempts');
    }
};
```

### Migration: Friendships Table

**File**: `database/migrations/2024_01_10_000000_create_friendships_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('friendships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('friend_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['pending', 'accepted', 'blocked'])->default('pending');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
            
            $table->unique(['user_id', 'friend_id']);
            $table->index('user_id');
            $table->index('friend_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('friendships');
    }
};
```

### Migration: Transactions Table (For Coins)

**File**: `database/migrations/2024_01_11_000000_create_transactions_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['earned', 'spent', 'purchased', 'refund'])->default('earned');
            $table->integer('amount'); // Can be negative for spending
            $table->integer('balance_after');
            $table->string('description');
            $table->string('reference_type')->nullable(); // Morph type
            $table->unsignedBigInteger('reference_id')->nullable(); // Morph id
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index('user_id');
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
```

---

## 📦 Eloquent Models

### User Model (Enhanced)

**File**: `app/Models/User.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'username', 'avatar', 'bio',
        'xp', 'level', 'coins', 'rank',
        'preferred_language', 'settings',
        'is_guest', 'guest_expires_at',
        'is_premium', 'premium_until',
        'referral_code', 'referred_by_id',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'settings' => 'array',
        'last_login_date' => 'date',
        'guest_expires_at' => 'datetime',
        'premium_until' => 'datetime',
        'is_guest' => 'boolean',
        'is_premium' => 'boolean',
    ];

    // Boot method
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($user) {
            if (!$user->username) {
                $user->username = 'user_' . Str::random(8);
            }
            if (!$user->referral_code) {
                $user->referral_code = strtoupper(Str::random(8));
            }
        });
    }

    // Relationships
    public function matchesAsPlayer1()
    {
        return $this->hasMany(Match::class, 'player1_id');
    }

    public function matchesAsPlayer2()
    {
        return $this->hasMany(Match::class, 'player2_id');
    }

    public function matches()
    {
        return Match::where('player1_id', $this->id)
            ->orWhere('player2_id', $this->id);
    }

    public function moves()
    {
        return $this->hasMany(MatchMove::class);
    }

    public function achievements()
    {
        return $this->belongsToMany(Achievement::class, 'user_achievements')
            ->withPivot(['progress', 'is_claimed', 'unlocked_at'])
            ->withTimestamps();
    }

    public function dailyPuzzleAttempts()
    {
        return $this->hasMany(DailyPuzzleAttempt::class);
    }

    public function friends()
    {
        return $this->belongsToMany(User::class, 'friendships', 'user_id', 'friend_id')
            ->wherePivot('status', 'accepted')
            ->withTimestamps();
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function referredUsers()
    {
        return $this->hasMany(User::class, 'referred_by_id');
    }

    public function referredBy()
    {
        return $this->belongsTo(User::class, 'referred_by_id');
    }

    // Helper methods
    public function addXP(int $amount): void
    {
        $this->xp += $amount;
        $this->checkLevelUp();
        $this->save();
    }

    public function addCoins(int $amount, string $description, $reference = null): void
    {
        $this->coins += $amount;
        $balanceAfter = $this->coins;
        $this->save();

        // Record transaction
        $this->transactions()->create([
            'type' => $amount > 0 ? 'earned' : 'spent',
            'amount' => $amount,
            'balance_after' => $balanceAfter,
            'description' => $description,
            'reference_type' => $reference ? get_class($reference) : null,
            'reference_id' => $reference?->id,
        ]);
    }

    protected function checkLevelUp(): void
    {
        $newLevel = $this->calculateLevel($this->xp);
        if ($newLevel > $this->level) {
            $this->level = $newLevel;
            // Trigger level up event
            event(new \App\Events\UserLeveledUp($this, $newLevel));
        }
    }

    protected function calculateLevel(int $xp): int
    {
        // Simple formula: level = floor(sqrt(xp / 100)) + 1
        // Level 1: 0 XP, Level 2: 100 XP, Level 3: 400 XP, Level 4: 900 XP, etc.
        return min(50, floor(sqrt($xp / 100)) + 1);
    }

    public function xpToNextLevel(): int
    {
        $nextLevel = $this->level + 1;
        $xpNeeded = pow($nextLevel - 1, 2) * 100;
        return max(0, $xpNeeded - $this->xp);
    }

    public function winRate(): float
    {
        if ($this->total_matches === 0) {
            return 0;
        }
        return round(($this->wins / $this->total_matches) * 100, 2);
    }

    public function accuracy(): float
    {
        if ($this->questions_answered === 0) {
            return 0;
        }
        return round(($this->correct_answers / $this->questions_answered) * 100, 2);
    }

    public function isPremium(): bool
    {
        return $this->is_premium && $this->premium_until && $this->premium_until->isFuture();
    }

    public function isGuest(): bool
    {
        return $this->is_guest;
    }

    public function canPlay(): bool
    {
        if ($this->is_guest) {
            return $this->guest_expires_at && $this->guest_expires_at->isFuture();
        }
        return true;
    }
}
```

### Category Model

**File**: `app/Models/Category.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'description', 'icon', 'color', 'order', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($category) {
            if (!$category->slug) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function matches()
    {
        return $this->hasMany(Match::class);
    }

    public function activeQuestions()
    {
        return $this->questions()->where('is_active', true);
    }
}
```

### Question Model (Enhanced)

**File**: `app/Models/Question.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id', 'question', 'difficulty', 'language',
        'time_limit', 'xp_reward', 'coins_reward',
        'is_active', 'explanation', 'tags',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'tags' => 'array',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function answers()
    {
        return $this->hasMany(Answer::class);
    }

    public function correctAnswer()
    {
        return $this->hasOne(Answer::class)->where('is_correct', true);
    }

    public function moves()
    {
        return $this->hasMany(MatchMove::class);
    }

    public function incrementUsage(bool $wasCorrect): void
    {
        $this->times_used++;
        if ($wasCorrect) {
            $this->times_correct++;
        } else {
            $this->times_incorrect++;
        }
        $this->save();
    }

    public function getDifficultyMultiplier(): float
    {
        return match($this->difficulty) {
            'easy' => 1.0,
            'medium' => 1.5,
            'hard' => 2.0,
            default => 1.0,
        };
    }
}
```

### Answer Model

Already exists, no changes needed.

### Match Model

**File**: `app/Models/Match.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Match extends Model
{
    use HasFactory;

    protected $fillable = [
        'match_code', 'game_type', 'mode',
        'player1_id', 'player2_id', 'player1_symbol', 'player2_symbol',
        'status', 'current_turn_user_id', 'board_state',
        'winner_id', 'result',
        'current_question_id', 'question_history',
        'started_at', 'ended_at', 'turn_time_limit', 'turn_started_at',
        'xp_awarded', 'coins_awarded',
        'category_id', 'difficulty',
    ];

    protected $casts = [
        'board_state' => 'array',
        'question_history' => 'array',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'turn_started_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($match) {
            if (!$match->match_code) {
                $match->match_code = strtoupper(Str::random(6));
            }
        });
    }

    public function player1()
    {
        return $this->belongsTo(User::class, 'player1_id');
    }

    public function player2()
    {
        return $this->belongsTo(User::class, 'player2_id');
    }

    public function winner()
    {
        return $this->belongsTo(User::class, 'winner_id');
    }

    public function currentTurnUser()
    {
        return $this->belongsTo(User::class, 'current_turn_user_id');
    }

    public function currentQuestion()
    {
        return $this->belongsTo(Question::class, 'current_question_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function moves()
    {
        return $this->hasMany(MatchMove::class)->orderBy('move_number');
    }

    public function getOpponent(User $user)
    {
        return $user->id === $this->player1_id ? $this->player2 : $this->player1;
    }

    public function isPlayerTurn(User $user): bool
    {
        return $this->current_turn_user_id === $user->id;
    }
}
```

*Continue with remaining models in next message...*
