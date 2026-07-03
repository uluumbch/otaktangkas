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
            $table->unsignedInteger('time_taken')->nullable(); // seconds
            $table->unsignedInteger('moves_used')->default(0);
            $table->unsignedInteger('correct_answers')->default(0);
            $table->unsignedInteger('score')->default(0);
            $table->unsignedInteger('xp_earned')->default(0);
            $table->unsignedInteger('coins_earned')->default(0);
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
