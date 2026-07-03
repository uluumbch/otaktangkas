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
            $table->foreignId('player1_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('player2_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('player1_symbol')->default('X');
            $table->string('player2_symbol')->default('O');

            // Game state
            $table->enum('status', ['waiting', 'in_progress', 'completed', 'abandoned'])->default('waiting');
            $table->foreignId('current_turn_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('board_state')->nullable();
            $table->foreignId('winner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('result', ['player1_win', 'player2_win', 'draw', 'abandoned'])->nullable();

            // Questions
            $table->foreignId('current_question_id')->nullable()->constrained('questions')->nullOnDelete();
            $table->json('question_history')->nullable();

            // Timing
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('turn_time_limit')->default(30); // seconds per turn
            $table->timestamp('turn_started_at')->nullable();

            // Rewards
            $table->unsignedInteger('xp_awarded')->default(0);
            $table->unsignedInteger('coins_awarded')->default(0);

            // Settings
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('difficulty', ['easy', 'medium', 'hard', 'mixed'])->default('mixed');

            $table->timestamps();

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
