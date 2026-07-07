<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'OtakTangkas') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-playfield h-full text-gray-900 antialiased">
    @auth
        @php
            $navLinks = [
                'dashboard' => 'Dashboard',
                'practice' => 'Latihan',
                'quick-play' => 'Quick Play',
                'daily-puzzle' => 'Puzzle',
                'leaderboard' => 'Peringkat',
                'achievements' => 'Prestasi',
            ];
        @endphp
        <nav class="sticky top-0 z-40 border-b border-white/60 bg-white/80 shadow-xs backdrop-blur">
            <div class="mx-auto flex max-w-5xl flex-wrap items-center gap-x-6 gap-y-2 px-4 py-3">
                <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-1 text-lg font-black tracking-tight">
                    🧠 <span class="bg-linear-to-r from-primary-600 to-secondary-600 bg-clip-text text-transparent">OtakTangkas</span>
                </a>

                <div class="flex items-center gap-1 text-sm">
                    @foreach ($navLinks as $route => $label)
                        <a href="{{ route($route) }}" wire:navigate
                           class="rounded-full px-3 py-1.5 font-bold transition {{ request()->routeIs($route) ? 'bg-primary-600 text-white shadow-xs' : 'text-gray-600 hover:bg-primary-50 hover:text-primary-700' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>

                <div class="ml-auto flex items-center gap-2 text-sm">
                    <span class="chip-hud hidden bg-primary-100 text-primary-800 sm:inline-flex">
                        ⭐ Lv {{ auth()->user()->level }}
                    </span>
                    <span class="chip-hud bg-amber-100 text-amber-800">
                        🪙 {{ number_format(auth()->user()->coins) }}
                    </span>
                    <span class="hidden font-bold text-gray-700 md:inline">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn-game-light px-3 py-1.5 text-sm">
                            Keluar
                        </button>
                    </form>
                </div>
            </div>
        </nav>
    @endauth

    <main class="min-h-full">
        {{ $slot }}
    </main>
</body>
</html>
