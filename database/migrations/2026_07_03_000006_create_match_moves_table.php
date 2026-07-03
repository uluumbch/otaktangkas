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
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('answer_id')->nullable()->constrained()->nullOnDelete();

            // Move details
            $table->unsignedInteger('move_number'); // 1, 2, 3, ...
            $table->string('position'); // For tic-tac-toe: "0,0", "1,2", etc.
            $table->boolean('is_correct');
            $table->unsignedInteger('time_taken'); // seconds

            // Rewards
            $table->unsignedInteger('xp_earned')->default(0);
            $table->unsignedInteger('coins_earned')->default(0);

            $table->timestamps();

            $table->index('match_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_moves');
    }
};
