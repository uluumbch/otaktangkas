<x-layouts.app title="Dashboard - OtakTangkas">
    @php
        $user = auth()->user();
        $levelFloor = ($user->level - 1) ** 2 * 100;
        $levelCeil = $user->level ** 2 * 100;
        $xpProgress = $levelCeil > $levelFloor
            ? min(100, max(0, (int) round(($user->xp - $levelFloor) / ($levelCeil - $levelFloor) * 100)))
            : 100;
    @endphp

    <div class="mx-auto max-w-3xl px-4 py-8">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-3xl font-black tracking-tight text-gray-900">Halo, {{ $user->name }}! 👋</h1>
                <p class="text-sm font-medium text-gray-500">{{ '@'.$user->username }} · peringkat <span class="font-bold capitalize">{{ $user->rank }}</span></p>
            </div>
        </div>

        {{-- HUD: level + XP progress --}}
        <div class="shadow-game mt-5 rounded-2xl bg-white p-4">
            <div class="flex items-center justify-between text-sm font-bold">
                <span class="text-primary-700">⭐ Level {{ $user->level }}</span>
                <span class="text-gray-400">{{ number_format($user->xp) }} / {{ number_format($levelCeil) }} XP</span>
            </div>
            <div class="mt-2 h-3 overflow-hidden rounded-full bg-gray-100">
                <div class="h-full rounded-full bg-linear-to-r from-primary-500 to-secondary-500 transition-all"
                     style="width: {{ $xpProgress }}%"></div>
            </div>
        </div>

        {{-- Game menu --}}
        <div class="mt-6 grid gap-4 sm:grid-cols-3">
            <a href="{{ route('practice') }}" wire:navigate
               class="shadow-game group rounded-3xl bg-linear-to-br from-primary-500 to-primary-700 p-5 text-white transition hover:-translate-y-1 hover:shadow-lg">
                <div class="animate-float text-4xl">🎯</div>
                <h2 class="mt-3 text-lg font-black tracking-tight">Mode Latihan</h2>
                <p class="mt-0.5 text-sm text-primary-100">Lawan AI untuk berlatih.</p>
                <span class="mt-3 inline-block rounded-full bg-white/20 px-3 py-1 text-xs font-bold transition group-hover:bg-white/30">Main →</span>
            </a>

            <a href="{{ route('quick-play') }}" wire:navigate
               class="shadow-game group rounded-3xl bg-linear-to-br from-secondary-500 to-secondary-700 p-5 text-white transition hover:-translate-y-1 hover:shadow-lg">
                <div class="animate-float text-4xl" style="animation-delay: 0.4s">⚔️</div>
                <h2 class="mt-3 text-lg font-black tracking-tight">Quick Play</h2>
                <p class="mt-0.5 text-sm text-secondary-100">Duel pemain lain langsung.</p>
                <span class="mt-3 inline-block rounded-full bg-white/20 px-3 py-1 text-xs font-bold transition group-hover:bg-white/30">Main →</span>
            </a>

            <a href="{{ route('daily-puzzle') }}" wire:navigate
               class="shadow-game group rounded-3xl bg-linear-to-br from-amber-400 to-orange-600 p-5 text-white transition hover:-translate-y-1 hover:shadow-lg">
                <div class="animate-float text-4xl" style="animation-delay: 0.8s">🧩</div>
                <h2 class="mt-3 text-lg font-black tracking-tight">Puzzle Harian</h2>
                <p class="mt-0.5 text-sm text-amber-100">Satu puzzle, sekali sehari.</p>
                <span class="mt-3 inline-block rounded-full bg-white/20 px-3 py-1 text-xs font-bold transition group-hover:bg-white/30">Main →</span>
            </a>
        </div>

        {{-- Stats HUD --}}
        <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
            <div class="shadow-game rounded-2xl bg-white p-4 text-center">
                <div class="text-2xl">🏅</div>
                <p class="mt-1 text-2xl font-black text-primary-600">{{ number_format($user->wins) }}</p>
                <p class="text-xs font-bold tracking-wide text-gray-400 uppercase">Menang</p>
            </div>
            <div class="shadow-game rounded-2xl bg-white p-4 text-center">
                <div class="text-2xl">🎮</div>
                <p class="mt-1 text-2xl font-black text-gray-800">{{ number_format($user->total_matches) }}</p>
                <p class="text-xs font-bold tracking-wide text-gray-400 uppercase">Main</p>
            </div>
            <div class="shadow-game rounded-2xl bg-white p-4 text-center">
                <div class="text-2xl">🔥</div>
                <p class="mt-1 text-2xl font-black text-orange-500">{{ number_format($user->win_streak) }}</p>
                <p class="text-xs font-bold tracking-wide text-gray-400 uppercase">Streak</p>
            </div>
            <div class="shadow-game rounded-2xl bg-white p-4 text-center">
                <div class="text-2xl">🪙</div>
                <p class="mt-1 text-2xl font-black text-amber-500">{{ number_format($user->coins) }}</p>
                <p class="text-xs font-bold tracking-wide text-gray-400 uppercase">Koin</p>
            </div>
        </div>

        {{-- Quick links --}}
        <div class="mt-6 grid gap-4 sm:grid-cols-2">
            <a href="{{ route('leaderboard') }}" wire:navigate
               class="shadow-game flex items-center justify-between rounded-2xl bg-white p-4 font-bold text-gray-800 transition hover:-translate-y-0.5">
                <span>🏆 Peringkat</span><span class="text-gray-300">→</span>
            </a>
            <a href="{{ route('achievements') }}" wire:navigate
               class="shadow-game flex items-center justify-between rounded-2xl bg-white p-4 font-bold text-gray-800 transition hover:-translate-y-0.5">
                <span>🎖️ Prestasi</span><span class="text-gray-300">→</span>
            </a>
        </div>
    </div>
</x-layouts.app>
