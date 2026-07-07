<?php

use App\Livewire\Achievements;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Game\Play as GamePlay;
use App\Livewire\Leaderboard;
use App\Livewire\Practice\Play;
use App\Livewire\QuickPlay\Lobby;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::middleware('guest')->group(function () {
    Route::get('/register', Register::class)->name('register');
    Route::get('/login', Login::class)->name('login');
});

Route::middleware('auth')->group(function () {
    Route::view('/dashboard', 'dashboard')->name('dashboard');

    Route::get('/practice', Play::class)->name('practice');
    Route::get('/quick-play', Lobby::class)->name('quick-play');
    Route::get('/match/{match}', GamePlay::class)->name('match');
    Route::get('/leaderboard', Leaderboard::class)->name('leaderboard');
    Route::get('/achievements', Achievements::class)->name('achievements');

    Route::post('/logout', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    })->name('logout');
});
