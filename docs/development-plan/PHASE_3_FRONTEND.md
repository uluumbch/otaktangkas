# Phase 3: Frontend Development

## Overview
Complete UI/UX implementation for OtakTangkas with mobile-first design, Tailwind CSS styling, and interactive Livewire components optimized for the Indonesian market.

## Table of Contents
- [Tailwind Configuration](#tailwind-configuration)
- [Layout System](#layout-system)
- [Dashboard Design](#dashboard-design)
- [Game Board UI](#game-board-ui)
- [Question Components](#question-components)
- [Livewire Components](#livewire-components)
- [Responsive Design](#responsive-design)
- [Indonesian Market Considerations](#indonesian-market-considerations)
- [Alpine.js Interactions](#alpinejs-interactions)

---

## Tailwind Configuration

Tailwind CSS 4 uses **CSS-first configuration**: there is no `tailwind.config.js`. All design tokens live in `resources/css/app.css` via the `@theme` directive, and template detection is automatic (use `@source` only for non-standard paths).

### Custom Configuration (`resources/css/app.css`)

```css
@import 'tailwindcss';

/* Official plugins */
@plugin '@tailwindcss/forms';
@plugin '@tailwindcss/typography';

@theme {
  /* Brand colors optimized for Indonesian market */
  --color-primary-50: #f0f9ff;
  --color-primary-100: #e0f2fe;
  --color-primary-200: #bae6fd;
  --color-primary-300: #7dd3fc;
  --color-primary-400: #38bdf8;
  --color-primary-500: #0ea5e9; /* Main brand color */
  --color-primary-600: #0284c7;
  --color-primary-700: #0369a1;
  --color-primary-800: #075985;
  --color-primary-900: #0c4a6e;

  --color-secondary-50: #fdf4ff;
  --color-secondary-100: #fae8ff;
  --color-secondary-200: #f5d0fe;
  --color-secondary-300: #f0abfc;
  --color-secondary-400: #e879f9;
  --color-secondary-500: #d946ef;
  --color-secondary-600: #c026d3;
  --color-secondary-700: #a21caf;
  --color-secondary-800: #86198f;
  --color-secondary-900: #701a75;

  --color-success: #10b981;
  --color-warning: #f59e0b;
  --color-danger: #ef4444;
  --color-info: #3b82f6;

  /* Fonts */
  --font-sans: 'Inter', system-ui, sans-serif;
  --font-display: 'Poppins', system-ui, sans-serif;

  /* Custom animations */
  --animate-pulse-slow: pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
  --animate-bounce-slow: bounce 2s infinite;
  --animate-wiggle: wiggle 1s ease-in-out infinite;
  --animate-fade-in: fadeIn 0.5s ease-in-out;
  --animate-slide-up: slideUp 0.3s ease-out;
  --animate-slide-down: slideDown 0.3s ease-out;

  @keyframes wiggle {
    0%, 100% { transform: rotate(-3deg); }
    50% { transform: rotate(3deg); }
  }
  @keyframes fadeIn {
    0% { opacity: 0; }
    100% { opacity: 1; }
  }
  @keyframes slideUp {
    0% { transform: translateY(10px); opacity: 0; }
    100% { transform: translateY(0); opacity: 1; }
  }
  @keyframes slideDown {
    0% { transform: translateY(-10px); opacity: 0; }
    100% { transform: translateY(0); opacity: 1; }
  }

  /* Custom shadows */
  --shadow-game: 0 10px 40px -10px rgba(0, 0, 0, 0.3);
  --shadow-card: 0 4px 6px -1px rgba(0, 0, 0, 0.1);

  /* Extra breakpoint */
  --breakpoint-xs: 475px;
}
```

### Installation Checklist
- [ ] Install Tailwind CSS 4: `npm install tailwindcss @tailwindcss/vite` (no PostCSS/autoprefixer needed)
- [ ] Register the plugin in `vite.config.js` (see Phase 0)
- [ ] Install plugins: `npm install -D @tailwindcss/forms @tailwindcss/typography` and load via `@plugin`
- [ ] Install custom fonts from Google Fonts
- [ ] Build CSS: `npm run build`

---

## Layout System

### App Layout (`resources/views/layouts/app.blade.php`)

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="OtakTangkas - Game Trivia Tic Tac Toe Indonesia">
    
    <title>{{ config('app.name', 'OtakTangkas') }} - @yield('title', 'Game Trivia Seru')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#0ea5e9">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/images/icon-192.png">
</head>
<body class="bg-gray-50 font-sans antialiased" x-data="{ sidebarOpen: false }">
    
    <!-- Navigation -->
    <nav class="bg-white shadow-xs border-b border-gray-200 sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="{{ route('dashboard') }}" class="flex items-center space-x-2">
                        <div class="w-10 h-10 bg-linear-to-br from-primary-500 to-primary-600 rounded-lg flex items-center justify-center">
                            <i class="fas fa-brain text-white text-xl"></i>
                        </div>
                        <span class="text-xl font-display font-bold text-gray-900 hidden sm:block">OtakTangkas</span>
                    </a>
                </div>

                <!-- Desktop Navigation -->
                <div class="hidden md:flex items-center space-x-4">
                    <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                    <a href="{{ route('game.quick-play') }}" class="nav-link {{ request()->routeIs('game.quick-play') ? 'active' : '' }}">
                        <i class="fas fa-gamepad"></i> Main
                    </a>
                    <a href="{{ route('leaderboard') }}" class="nav-link {{ request()->routeIs('leaderboard') ? 'active' : '' }}">
                        <i class="fas fa-trophy"></i> Leaderboard
                    </a>
                </div>

                <!-- User Menu -->
                <div class="flex items-center space-x-3">
                    <!-- Coins Display -->
                    <div class="flex items-center space-x-2 bg-yellow-50 px-3 py-1.5 rounded-full">
                        <i class="fas fa-coins text-yellow-500"></i>
                        <span class="font-semibold text-gray-900">{{ auth()->user()->coins }}</span>
                    </div>

                    <!-- Profile Dropdown -->
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" class="flex items-center space-x-2 focus:outline-hidden">
                            <img src="{{ auth()->user()->avatar_url }}" alt="Avatar" class="w-9 h-9 rounded-full border-2 border-primary-500">
                        </button>

                        <!-- Dropdown Menu -->
                        <div x-show="open" 
                             @click.away="open = false"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-1 z-50">
                            <a href="{{ route('profile') }}" class="dropdown-item">
                                <i class="fas fa-user"></i> Profile
                            </a>
                            <a href="{{ route('settings') }}" class="dropdown-item">
                                <i class="fas fa-cog"></i> Pengaturan
                            </a>
                            <hr class="my-1">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item text-red-600 w-full text-left">
                                    <i class="fas fa-sign-out-alt"></i> Keluar
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Mobile Menu Button -->
                    <button @click="sidebarOpen = true" class="md:hidden p-2 text-gray-600 hover:text-gray-900">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Mobile Sidebar -->
    <div x-show="sidebarOpen" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         class="fixed inset-0 z-50 md:hidden">
        <div class="absolute inset-0 bg-gray-600/75" @click="sidebarOpen = false"></div>
        <div class="absolute right-0 top-0 h-full w-64 bg-white shadow-xl">
            <div class="p-4">
                <button @click="sidebarOpen = false" class="absolute top-4 right-4 text-gray-500">
                    <i class="fas fa-times text-xl"></i>
                </button>
                
                <div class="mt-8 space-y-2">
                    <a href="{{ route('dashboard') }}" class="mobile-nav-link">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                    <a href="{{ route('game.quick-play') }}" class="mobile-nav-link">
                        <i class="fas fa-gamepad"></i> Main
                    </a>
                    <a href="{{ route('leaderboard') }}" class="mobile-nav-link">
                        <i class="fas fa-trophy"></i> Leaderboard
                    </a>
                    <a href="{{ route('achievements') }}" class="mobile-nav-link">
                        <i class="fas fa-medal"></i> Pencapaian
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        {{ $slot }}
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="text-center text-gray-600 text-sm">
                <p>&copy; 2024 OtakTangkas. Semua hak dilindungi.</p>
            </div>
        </div>
    </footer>

    @livewireScripts
    
    <!-- Toast Notifications -->
    <div x-data="{ show: false, message: '' }" 
         @notify.window="show = true; message = $event.detail; setTimeout(() => show = false, 3000)"
         x-show="show"
         x-transition
         class="fixed top-4 right-4 z-50">
        <div class="bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg">
            <span x-text="message"></span>
        </div>
    </div>

    <style>
        .nav-link {
            @apply px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:text-primary-600 hover:bg-gray-50 transition-colors;
        }
        .nav-link.active {
            @apply text-primary-600 bg-primary-50;
        }
        .dropdown-item {
            @apply block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors;
        }
        .mobile-nav-link {
            @apply block px-4 py-3 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors;
        }
    </style>
</body>
</html>
```

### Guest Layout (`resources/views/layouts/guest.blade.php`)

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>{{ config('app.name', 'OtakTangkas') }} - @yield('title', 'Masuk')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-linear-to-br from-primary-500 via-primary-600 to-secondary-600 min-h-screen flex items-center justify-center p-4">
    
    <div class="w-full max-w-md">
        <!-- Logo -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-white rounded-2xl shadow-xl mb-4">
                <i class="fas fa-brain text-primary-600 text-4xl"></i>
            </div>
            <h1 class="text-4xl font-display font-bold text-white">OtakTangkas</h1>
            <p class="text-primary-100 mt-2">Asah otak sambil bermain!</p>
        </div>

        <!-- Card -->
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            {{ $slot }}
        </div>

        <!-- Footer -->
        <div class="text-center mt-6 text-primary-100 text-sm">
            <p>&copy; 2024 OtakTangkas. Semua hak dilindungi.</p>
        </div>
    </div>

    @livewireScripts
</body>
</html>
```

---

## Dashboard Design

### Dashboard View (`resources/views/dashboard.blade.php`)

```blade
<x-app-layout>
    <div class="space-y-6">
        
        <!-- Welcome Banner -->
        <div class="bg-linear-to-r from-primary-500 to-secondary-600 rounded-2xl p-6 text-white shadow-xl">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-display font-bold">
                        Halo, {{ auth()->user()->name }}! 👋
                    </h1>
                    <p class="text-primary-100 mt-1">Siap untuk tantangan hari ini?</p>
                </div>
                <div class="hidden sm:block">
                    <div class="text-right">
                        <div class="text-4xl font-bold">Level {{ auth()->user()->level }}</div>
                        <div class="text-sm text-primary-100">{{ auth()->user()->rank }}</div>
                    </div>
                </div>
            </div>

            <!-- XP Progress Bar -->
            <div class="mt-4">
                <div class="flex justify-between text-sm mb-1">
                    <span>XP Progress</span>
                    <span>{{ auth()->user()->xp }} / {{ auth()->user()->next_level_xp }}</span>
                </div>
                <div class="w-full bg-primary-700 rounded-full h-3">
                    <div class="bg-white rounded-full h-3 transition-all duration-500" 
                         style="width: {{ auth()->user()->xp_percentage }}%"></div>
                </div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Wins -->
            <div class="bg-white rounded-xl p-4 shadow-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">Menang</p>
                        <p class="text-2xl font-bold text-green-600">{{ $stats['wins'] }}</p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-trophy text-green-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <!-- Losses -->
            <div class="bg-white rounded-xl p-4 shadow-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">Kalah</p>
                        <p class="text-2xl font-bold text-red-600">{{ $stats['losses'] }}</p>
                    </div>
                    <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-times-circle text-red-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <!-- Win Streak -->
            <div class="bg-white rounded-xl p-4 shadow-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">Streak</p>
                        <p class="text-2xl font-bold text-orange-600">{{ $stats['win_streak'] }} 🔥</p>
                    </div>
                    <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-fire text-orange-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <!-- Total Games -->
            <div class="bg-white rounded-xl p-4 shadow-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">Total Game</p>
                        <p class="text-2xl font-bold text-blue-600">{{ $stats['total_games'] }}</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-gamepad text-blue-600 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            
            <!-- Quick Play -->
            <div class="bg-white rounded-xl shadow-card overflow-hidden group hover:shadow-xl transition-shadow">
                <div class="bg-linear-to-r from-primary-500 to-primary-600 p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-xl font-bold">Quick Play</h3>
                            <p class="text-primary-100 text-sm mt-1">Main dengan pemain acak</p>
                        </div>
                        <i class="fas fa-bolt text-4xl opacity-50"></i>
                    </div>
                </div>
                <div class="p-6">
                    <a href="{{ route('game.quick-play') }}" 
                       class="block w-full bg-primary-500 hover:bg-primary-600 text-white font-semibold py-3 rounded-lg text-center transition-colors">
                        Mulai Main
                    </a>
                </div>
            </div>

            <!-- Daily Puzzle -->
            <div class="bg-white rounded-xl shadow-card overflow-hidden group hover:shadow-xl transition-shadow">
                <div class="bg-linear-to-r from-secondary-500 to-secondary-600 p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-xl font-bold">Puzzle Harian</h3>
                            <p class="text-secondary-100 text-sm mt-1">Selesaikan puzzle hari ini</p>
                        </div>
                        <i class="fas fa-puzzle-piece text-4xl opacity-50"></i>
                    </div>
                </div>
                <div class="p-6">
                    @if($dailyPuzzleCompleted)
                        <div class="text-center text-green-600">
                            <i class="fas fa-check-circle text-4xl mb-2"></i>
                            <p class="font-semibold">Selesai! Kembali besok</p>
                        </div>
                    @else
                        <a href="{{ route('game.daily-puzzle') }}" 
                           class="block w-full bg-secondary-500 hover:bg-secondary-600 text-white font-semibold py-3 rounded-lg text-center transition-colors">
                            Mulai Puzzle
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <!-- Recent Achievements -->
        @if($recentAchievements->count() > 0)
        <div class="bg-white rounded-xl shadow-card p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900">Pencapaian Terbaru 🎉</h3>
                <a href="{{ route('achievements') }}" class="text-primary-600 text-sm hover:underline">
                    Lihat Semua
                </a>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach($recentAchievements as $achievement)
                <div class="text-center p-4 bg-gray-50 rounded-lg">
                    <div class="text-4xl mb-2">{{ $achievement->icon }}</div>
                    <h4 class="font-semibold text-sm text-gray-900">{{ $achievement->name }}</h4>
                    <p class="text-xs text-gray-600 mt-1">+{{ $achievement->xp_reward }} XP</p>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Leaderboard Preview -->
        <div class="bg-white rounded-xl shadow-card p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900">Leaderboard</h3>
                <a href="{{ route('leaderboard') }}" class="text-primary-600 text-sm hover:underline">
                    Lihat Semua
                </a>
            </div>
            <div class="space-y-3">
                @foreach($topPlayers as $index => $player)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center space-x-3">
                        <div class="text-lg font-bold {{ $index === 0 ? 'text-yellow-500' : ($index === 1 ? 'text-gray-400' : 'text-orange-600') }}">
                            #{{ $index + 1 }}
                        </div>
                        <img src="{{ $player->avatar_url }}" alt="{{ $player->name }}" class="w-10 h-10 rounded-full">
                        <div>
                            <p class="font-semibold text-gray-900">{{ $player->name }}</p>
                            <p class="text-xs text-gray-600">Level {{ $player->level }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="font-bold text-primary-600">{{ $player->xp }} XP</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

    </div>
</x-app-layout>
```

---

## Game Board UI

### Game Board Component (Livewire)

```blade
<!-- resources/views/livewire/game-board.blade.php -->
<div class="max-w-2xl mx-auto">
    
    <!-- Game Header -->
    <div class="bg-white rounded-xl shadow-card p-4 mb-6">
        <div class="flex items-center justify-between">
            <!-- Player 1 -->
            <div class="flex items-center space-x-3">
                <img src="{{ $game->player1->avatar_url }}" alt="{{ $game->player1->name }}" 
                     class="w-12 h-12 rounded-full border-2 {{ $game->current_turn === $game->player1_id ? 'border-primary-500' : 'border-gray-300' }}">
                <div>
                    <p class="font-semibold text-gray-900">{{ $game->player1->name }}</p>
                    <p class="text-sm text-gray-600">
                        <i class="fas fa-times text-primary-600"></i> Silang
                    </p>
                </div>
            </div>

            <!-- VS Badge -->
            <div class="text-center">
                <div class="bg-linear-to-r from-primary-500 to-secondary-600 text-white px-4 py-2 rounded-lg font-bold">
                    VS
                </div>
            </div>

            <!-- Player 2 -->
            <div class="flex items-center space-x-3">
                <div class="text-right">
                    <p class="font-semibold text-gray-900">{{ $game->player2->name }}</p>
                    <p class="text-sm text-gray-600">
                        <i class="fas fa-circle text-secondary-600"></i> Bulat
                    </p>
                </div>
                <img src="{{ $game->player2->avatar_url }}" alt="{{ $game->player2->name }}" 
                     class="w-12 h-12 rounded-full border-2 {{ $game->current_turn === $game->player2_id ? 'border-secondary-500' : 'border-gray-300' }}">
            </div>
        </div>
    </div>

    <!-- Game Board -->
    <div class="bg-white rounded-2xl shadow-game p-6">
        <div class="grid grid-cols-3 gap-3 mb-6">
            @for ($i = 0; $i < 9; $i++)
                <button 
                    wire:click="selectCell({{ $i }})"
                    @class([
                        'aspect-square rounded-xl border-4 transition-all duration-200 flex items-center justify-center text-4xl font-bold',
                        'border-primary-300 hover:border-primary-500 hover:bg-primary-50' => empty($game->board[$i]),
                        'border-primary-500 bg-primary-100 cursor-not-allowed' => $game->board[$i] === 'X',
                        'border-secondary-500 bg-secondary-100 cursor-not-allowed' => $game->board[$i] === 'O',
                        'cursor-not-allowed opacity-50' => $game->status !== 'in_progress' || $game->current_turn !== auth()->id(),
                    ])
                    {{ empty($game->board[$i]) && $game->status === 'in_progress' && $game->current_turn === auth()->id() ? '' : 'disabled' }}>
                    
                    @if($game->board[$i] === 'X')
                        <i class="fas fa-times text-primary-600"></i>
                    @elseif($game->board[$i] === 'O')
                        <i class="fas fa-circle text-secondary-600"></i>
                    @endif
                </button>
            @endfor
        </div>

        <!-- Turn Indicator -->
        <div class="text-center">
            @if($game->status === 'in_progress')
                @if($game->current_turn === auth()->id())
                    <p class="text-lg font-semibold text-green-600">
                        <i class="fas fa-hand-pointer animate-bounce"></i> Giliran Anda!
                    </p>
                @else
                    <p class="text-lg font-semibold text-gray-600">
                        <i class="fas fa-hourglass-half animate-pulse"></i> Menunggu lawan...
                    </p>
                @endif
            @elseif($game->status === 'completed')
                @if($game->winner_id === auth()->id())
                    <p class="text-2xl font-bold text-green-600">
                        <i class="fas fa-trophy"></i> Anda Menang! 🎉
                    </p>
                @elseif($game->winner_id)
                    <p class="text-2xl font-bold text-red-600">
                        <i class="fas fa-times-circle"></i> Anda Kalah
                    </p>
                @else
                    <p class="text-2xl font-bold text-gray-600">
                        <i class="fas fa-handshake"></i> Seri!
                    </p>
                @endif
            @endif
        </div>

        <!-- Actions -->
        @if($game->status === 'completed')
        <div class="mt-6 flex gap-3">
            <a href="{{ route('dashboard') }}" 
               class="flex-1 bg-gray-500 hover:bg-gray-600 text-white font-semibold py-3 rounded-lg text-center transition-colors">
                Dashboard
            </a>
            <a href="{{ route('game.quick-play') }}" 
               class="flex-1 bg-primary-500 hover:bg-primary-600 text-white font-semibold py-3 rounded-lg text-center transition-colors">
                Main Lagi
            </a>
        </div>
        @endif
    </div>

    <!-- Question Modal -->
    @if($showQuestionModal)
    <div class="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50"
         x-data="{ show: true }"
         x-show="show"
         x-transition>
        <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl">
            <!-- Question -->
            <div class="mb-6">
                <h3 class="text-xl font-bold text-gray-900 mb-4">Jawab Pertanyaan Ini:</h3>
                <p class="text-lg text-gray-700">{{ $currentQuestion->question }}</p>
            </div>

            <!-- Options -->
            <div class="space-y-3">
                @foreach($currentQuestion->options as $index => $option)
                <button 
                    wire:click="answerQuestion({{ $index }})"
                    class="w-full text-left p-4 border-2 border-gray-300 rounded-lg hover:border-primary-500 hover:bg-primary-50 transition-colors">
                    <span class="font-semibold text-gray-900">{{ chr(65 + $index) }}.</span>
                    <span class="ml-2 text-gray-700">{{ $option }}</span>
                </button>
                @endforeach
            </div>

            <!-- Timer -->
            <div class="mt-6">
                <div class="flex justify-between text-sm text-gray-600 mb-2">
                    <span>Waktu tersisa</span>
                    <span class="font-bold" x-data="{ time: 30 }" 
                          x-init="setInterval(() => { if(time > 0) time-- }, 1000)">
                        <span x-text="time"></span> detik
                    </span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-primary-500 h-2 rounded-full transition-all duration-1000" 
                         style="width: 100%"
                         x-data="{ width: 100 }"
                         x-init="setInterval(() => { if(width > 0) width -= 3.33 }, 1000)"
                         :style="'width: ' + width + '%'"></div>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>

<script>
    // Auto-refresh game state
    setInterval(() => {
        @this.call('refreshGame');
    }, 2000);
</script>
```

---

## Question Components

### Question Card Component

```blade
<!-- resources/views/components/question-card.blade.php -->
@props(['question', 'showAnswer' => false])

<div class="bg-white rounded-xl shadow-card p-6">
    <!-- Category Badge -->
    <div class="flex items-center justify-between mb-4">
        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-primary-100 text-primary-800">
            <i class="fas fa-tag mr-2"></i>
            {{ $question->category->name }}
        </span>
        <span class="text-sm text-gray-600">
            {{ $question->difficulty_label }}
        </span>
    </div>

    <!-- Question -->
    <h4 class="text-lg font-semibold text-gray-900 mb-4">{{ $question->question }}</h4>

    <!-- Options -->
    <div class="space-y-2">
        @foreach($question->options as $index => $option)
        <div @class([
            'p-3 rounded-lg border-2',
            'border-green-500 bg-green-50' => $showAnswer && $index === $question->correct_answer_index,
            'border-gray-300' => !$showAnswer || $index !== $question->correct_answer_index,
        ])>
            <span class="font-semibold">{{ chr(65 + $index) }}.</span>
            <span class="ml-2">{{ $option }}</span>
            @if($showAnswer && $index === $question->correct_answer_index)
                <i class="fas fa-check-circle text-green-600 float-right"></i>
            @endif
        </div>
        @endforeach
    </div>

    <!-- Explanation (if showing answer) -->
    @if($showAnswer && $question->explanation)
    <div class="mt-4 p-4 bg-blue-50 rounded-lg border border-blue-200">
        <p class="text-sm text-gray-700">
            <i class="fas fa-info-circle text-blue-600 mr-2"></i>
            {{ $question->explanation }}
        </p>
    </div>
    @endif
</div>
```

---

## Livewire Components

### Quick Play Component

```php
<?php
// app/Livewire/QuickPlay.php

namespace App\Livewire;

use App\Models\Game;
use App\Services\MatchmakingService;
use Livewire\Component;

class QuickPlay extends Component
{
    public $searching = false;
    public $gameId = null;
    public $timeElapsed = 0;

    protected $listeners = ['gameFound'];

    public function mount()
    {
        // Check if already in queue
        $existingGame = Game::where('status', 'waiting')
            ->where(function($query) {
                $query->where('player1_id', auth()->id())
                      ->orWhere('player2_id', auth()->id());
            })
            ->first();

        if ($existingGame) {
            $this->searching = true;
            $this->gameId = $existingGame->id;
        }
    }

    public function startSearch()
    {
        $this->searching = true;
        
        $matchmaking = app(MatchmakingService::class);
        $game = $matchmaking->findMatch(auth()->user());

        if ($game->status === 'in_progress') {
            return redirect()->route('game.play', $game);
        }

        $this->gameId = $game->id;
    }

    public function cancelSearch()
    {
        if ($this->gameId) {
            $game = Game::find($this->gameId);
            if ($game && $game->status === 'waiting') {
                $game->delete();
            }
        }

        $this->searching = false;
        $this->gameId = null;
    }

    public function checkMatch()
    {
        if (!$this->gameId) {
            return;
        }

        $game = Game::find($this->gameId);
        
        if ($game && $game->status === 'in_progress') {
            return redirect()->route('game.play', $game);
        }

        $this->timeElapsed++;
    }

    public function render()
    {
        return view('livewire.quick-play');
    }
}
```

```blade
<!-- resources/views/livewire/quick-play.blade.php -->
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-2xl shadow-game p-8 text-center">
        
        @if(!$searching)
            <!-- Start Screen -->
            <div>
                <div class="w-24 h-24 bg-linear-to-br from-primary-500 to-secondary-600 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-bolt text-white text-5xl"></i>
                </div>
                
                <h2 class="text-3xl font-display font-bold text-gray-900 mb-3">Quick Play</h2>
                <p class="text-gray-600 mb-8">Main dengan pemain acak dan uji kemampuanmu!</p>

                <button 
                    wire:click="startSearch"
                    class="bg-linear-to-r from-primary-500 to-primary-600 hover:from-primary-600 hover:to-primary-700 text-white font-bold text-lg px-12 py-4 rounded-xl transition-all transform hover:scale-105 shadow-lg">
                    Mulai Cari Lawan
                </button>

                <div class="mt-8 pt-8 border-t border-gray-200">
                    <p class="text-sm text-gray-600">Tips: Jawab pertanyaan dengan cepat dan benar untuk menang!</p>
                </div>
            </div>
        @else
            <!-- Searching Screen -->
            <div wire:poll.1s="checkMatch">
                <div class="relative mb-6">
                    <div class="w-24 h-24 mx-auto">
                        <div class="absolute inset-0 bg-primary-500 rounded-full animate-ping opacity-75"></div>
                        <div class="relative w-24 h-24 bg-linear-to-br from-primary-500 to-secondary-600 rounded-full flex items-center justify-center">
                            <i class="fas fa-search text-white text-4xl animate-pulse"></i>
                        </div>
                    </div>
                </div>

                <h2 class="text-2xl font-display font-bold text-gray-900 mb-3">Mencari Lawan...</h2>
                <p class="text-gray-600 mb-2">Mohon tunggu, kami sedang mencarikan lawan untukmu</p>
                <p class="text-sm text-gray-500 mb-8">Waktu tunggu: {{ $timeElapsed }} detik</p>

                <div class="flex items-center justify-center space-x-2 mb-8">
                    <div class="w-3 h-3 bg-primary-500 rounded-full animate-bounce" style="animation-delay: 0s"></div>
                    <div class="w-3 h-3 bg-primary-500 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                    <div class="w-3 h-3 bg-primary-500 rounded-full animate-bounce" style="animation-delay: 0.4s"></div>
                </div>

                <button 
                    wire:click="cancelSearch"
                    class="text-red-600 hover:text-red-700 font-semibold">
                    Batalkan Pencarian
                </button>
            </div>
        @endif

    </div>
</div>
```

---

## Responsive Design

### Breakpoint Strategy

```css
/* resources/css/app.css */

/* Mobile First Approach */
/* Base styles for mobile (320px - 640px) */
.container {
    @apply px-4;
}

/* Small devices (tablets, 640px and up) */
@screen sm {
    .container {
        @apply px-6;
    }
}

/* Medium devices (small laptops, 768px and up) */
@screen md {
    .container {
        @apply px-8;
    }
}

/* Large devices (desktops, 1024px and up) */
@screen lg {
    .container {
        @apply px-12;
    }
}

/* Extra large devices (large desktops, 1280px and up) */
@screen xl {
    .container {
        @apply px-16;
    }
}
```

### Mobile Optimization Checklist
- [ ] Touch-friendly buttons (min 44x44px)
- [ ] Readable font sizes (min 16px)
- [ ] Optimized images (WebP format, lazy loading)
- [ ] Fast loading times (<3s on 3G)
- [ ] No horizontal scrolling
- [ ] Thumb-friendly navigation
- [ ] Portrait and landscape support

---

## Indonesian Market Considerations

### Localization
- All text in Bahasa Indonesia
- Currency in Rupiah (Rp)
- Date format: DD/MM/YYYY
- Familiar cultural references in questions
- Indonesian payment methods support

### Mobile Optimization
- Data-efficient design (compressed images)
- Works on 3G/4G connections
- Progressive Web App (PWA) support
- Offline capabilities for basic features

### Design Preferences
- Bright, cheerful colors
- Gamification elements
- Social sharing features
- WhatsApp integration for sharing

---

## Alpine.js Interactions

### Interactive Components

```html
<!-- Collapsible Section -->
<div x-data="{ open: false }">
    <button @click="open = !open" class="w-full flex items-center justify-between p-4 bg-white rounded-lg">
        <span>Expand Section</span>
        <i :class="open ? 'fa-chevron-up' : 'fa-chevron-down'" class="fas"></i>
    </button>
    <div x-show="open" x-transition class="p-4 bg-gray-50 rounded-b-lg">
        Content here
    </div>
</div>

<!-- Countdown Timer -->
<div x-data="{ 
    timeLeft: 30, 
    interval: null,
    start() {
        this.interval = setInterval(() => {
            if (this.timeLeft > 0) {
                this.timeLeft--;
            } else {
                clearInterval(this.interval);
                this.$dispatch('timer-ended');
            }
        }, 1000);
    }
}" x-init="start()">
    <div class="text-2xl font-bold" x-text="timeLeft"></div>
</div>

<!-- Modal -->
<div x-data="{ open: false }">
    <button @click="open = true">Open Modal</button>
    
    <div x-show="open" 
         @click.away="open = false"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl p-6 max-w-md">
            <h3 class="text-xl font-bold mb-4">Modal Title</h3>
            <p>Modal content</p>
            <button @click="open = false" class="mt-4 btn-primary">Close</button>
        </div>
    </div>
</div>

<!-- Tabs -->
<div x-data="{ tab: 'tab1' }">
    <div class="flex space-x-2 border-b">
        <button @click="tab = 'tab1'" 
                :class="tab === 'tab1' ? 'border-primary-500 text-primary-600' : 'border-transparent'"
                class="px-4 py-2 border-b-2">
            Tab 1
        </button>
        <button @click="tab = 'tab2'" 
                :class="tab === 'tab2' ? 'border-primary-500 text-primary-600' : 'border-transparent'"
                class="px-4 py-2 border-b-2">
            Tab 2
        </button>
    </div>
    
    <div x-show="tab === 'tab1'" class="p-4">Tab 1 content</div>
    <div x-show="tab === 'tab2'" class="p-4">Tab 2 content</div>
</div>
```

---

## Completion Checklist

### Setup Phase
- [ ] Install and configure Tailwind CSS 4 (`@tailwindcss/vite` plugin)
- [ ] Install @tailwindcss/forms and @tailwindcss/typography (loaded via `@plugin`)
- [ ] Configure custom colors and fonts in `@theme` (`resources/css/app.css`)
- [ ] Verify Alpine.js works (bundled with Livewire 4 — no separate setup)
- [ ] Install Font Awesome icons

### Layout Implementation
- [ ] Create app layout
- [ ] Create guest layout
- [ ] Implement navigation menu
- [ ] Add mobile sidebar
- [ ] Create footer

### Dashboard
- [ ] Welcome banner with user info
- [ ] Stats grid (wins, losses, streak, total games)
- [ ] Quick action cards
- [ ] Recent achievements section
- [ ] Leaderboard preview

### Game UI
- [ ] Game board component
- [ ] Player info cards
- [ ] Turn indicator
- [ ] Question modal
- [ ] Result screen

### Responsive Design
- [ ] Test on mobile devices (320px - 640px)
- [ ] Test on tablets (640px - 1024px)
- [ ] Test on desktop (1024px+)
- [ ] Verify touch interactions
- [ ] Check landscape orientation

### Testing
- [ ] Cross-browser testing (Chrome, Firefox, Safari)
- [ ] Mobile device testing (Android, iOS)
- [ ] Performance testing (Lighthouse)
- [ ] Accessibility testing

---

## Troubleshooting

### Common Issues

**Tailwind classes not working**
```bash
# Rebuild CSS
npm run build

# Tailwind 4 detects templates automatically; if classes come from a
# non-standard path (e.g. vendor packages), add an @source line in
# resources/css/app.css
# Clear browser cache
```

**Alpine.js not initializing**
```html
<!-- Alpine is bundled with Livewire 4 and starts automatically. -->
<!-- Do NOT load Alpine separately (via CDN or import) — a second
     instance breaks Livewire's reactivity. -->
<!-- If Alpine directives don't run, verify Livewire's assets load
     (they are auto-injected when a Livewire component is on the page). -->
```

**Livewire not updating**
```bash
# Clear compiled views
php artisan view:clear

# Livewire 3/4 auto-injects its scripts — @livewireScripts is only
# needed if auto-injection was disabled in config/livewire.php
```

---

## Related Documentation
- [PHASE_1_FOUNDATION.md](./PHASE_1_FOUNDATION.md)
- [PHASE_2_GAME_ENGINE.md](./PHASE_2_GAME_ENGINE.md)
- [PHASE_4_PROGRESSION.md](./PHASE_4_PROGRESSION.md)
- [PHASE_5_SOLO_MODES.md](./PHASE_5_SOLO_MODES.md)

---

**Last Updated:** July 2026
**Status:** Ready for Implementation
