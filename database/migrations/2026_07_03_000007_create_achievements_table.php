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
            $table->string('icon')->nullable();
            $table->enum('rarity', ['common', 'rare', 'epic', 'legendary'])->default('common');

            // Unlock criteria (stored as JSON for flexibility), e.g. {"type":"wins","value":10}
            $table->json('criteria');

            // Rewards
            $table->unsignedInteger('xp_reward')->default(0);
            $table->unsignedInteger('coins_reward')->default(0);

            // Display
            $table->unsignedInteger('order')->default(0);
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
