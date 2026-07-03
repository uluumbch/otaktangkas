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
            $table->unsignedInteger('xp')->default(0);
            $table->unsignedInteger('level')->default(1);
            $table->unsignedInteger('coins')->default(100); // Starting coins
            $table->string('rank')->default('bronze'); // bronze, silver, gold, platinum, diamond

            // Statistics
            $table->unsignedInteger('total_matches')->default(0);
            $table->unsignedInteger('wins')->default(0);
            $table->unsignedInteger('losses')->default(0);
            $table->unsignedInteger('draws')->default(0);
            $table->unsignedInteger('win_streak')->default(0);
            $table->unsignedInteger('best_win_streak')->default(0);
            $table->unsignedInteger('questions_answered')->default(0);
            $table->unsignedInteger('correct_answers')->default(0);

            // Streaks
            $table->unsignedInteger('daily_login_streak')->default(0);
            $table->date('last_login_date')->nullable();

            // Premium (feature deferred to post-MVP; columns kept for forward-compat)
            $table->boolean('is_premium')->default(false);
            $table->timestamp('premium_until')->nullable();

            // Guest users
            $table->boolean('is_guest')->default(false);
            $table->timestamp('guest_expires_at')->nullable();

            // Settings
            $table->string('preferred_language')->default('id'); // id or en
            $table->json('settings')->nullable();

            // Referral
            $table->string('referral_code')->unique()->nullable();
            $table->foreignId('referred_by_id')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['referred_by_id']);
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
                'referral_code', 'referred_by_id',
            ]);
        });
    }
};
