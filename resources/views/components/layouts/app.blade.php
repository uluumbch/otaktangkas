<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'OtakTangkas') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-gray-50 text-gray-900 antialiased">
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
        <nav class="sticky top-0 z-40 border-b border-gray-200 bg-white/90 backdrop-blur">
            <div class="mx-auto flex max-w-5xl flex-wrap items-center gap-x-6 gap-y-2 px-4 py-3">
                <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-1 text-lg font-bold text-primary-600">
                    🧠 <span>OtakTangkas</span>
                </a>

                <div class="flex items-center gap-1 text-sm">
                    @foreach ($navLinks as $route => $label)
                        <a href="{{ route($route) }}" wire:navigate
                           class="rounded-lg px-3 py-1.5 font-medium transition {{ request()->routeIs($route) ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>

                <div class="ml-auto flex items-center gap-3 text-sm">
                    <span class="hidden rounded-full bg-primary-100 px-2.5 py-1 font-medium text-primary-800 sm:inline">
                        Lv {{ auth()->user()->level }}
                    </span>
                    <span class="rounded-full bg-yellow-100 px-2.5 py-1 font-medium text-yellow-800">
                        🪙 {{ number_format(auth()->user()->coins) }}
                    </span>
                    <span class="hidden font-medium text-gray-700 md:inline">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded-lg border border-gray-300 px-3 py-1.5 font-medium text-gray-600 transition hover:bg-gray-50">
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
