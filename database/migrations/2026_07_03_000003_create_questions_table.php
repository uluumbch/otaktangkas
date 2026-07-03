<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->text('question');
            $table->enum('difficulty', ['easy', 'medium', 'hard'])->default('medium');
            $table->string('language')->default('id'); // id or en
            $table->unsignedInteger('time_limit')->default(30); // seconds
            $table->unsignedInteger('xp_reward')->default(10);
            $table->unsignedInteger('coins_reward')->default(5);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('times_used')->default(0);
            $table->unsignedInteger('times_correct')->default(0);
            $table->unsignedInteger('times_incorrect')->default(0);
            $table->text('explanation')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();

            $table->index(['category_id', 'difficulty', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
