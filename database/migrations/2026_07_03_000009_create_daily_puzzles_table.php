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
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->enum('difficulty', ['easy', 'medium', 'hard'])->default('medium');
            $table->json('question_ids');
            $table->json('puzzle_config')->nullable();
            $table->unsignedInteger('xp_reward')->default(50);
            $table->unsignedInteger('coins_reward')->default(25);
            $table->unsignedInteger('time_limit')->default(300); // 5 minutes
            $table->unsignedInteger('attempts_count')->default(0);
            $table->unsignedInteger('completed_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_puzzles');
    }
};
