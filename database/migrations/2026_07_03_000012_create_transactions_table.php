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
