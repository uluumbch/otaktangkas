<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->enum('game_type', ['tic_tac_toe', 'memory_match', 'connect_four'])
                ->default('tic_tac_toe')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->enum('game_type', ['tic_tac_toe', 'memory_match'])
                ->default('tic_tac_toe')
                ->change();
        });
    }
};
