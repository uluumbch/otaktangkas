<x-layouts.app title="Dashboard - OtakTangkas">
    <div class="mx-auto max-w-3xl px-4 py-12">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Halo, {{ auth()->user()->name }}! 👋</h1>
                <p class="text-sm text-gray-600">@{{ auth()->user()->username }}</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                    Keluar
                </button>
            </form>
        </div>

        <div class="mt-8 grid gap-4 sm:grid-cols-2">
            <a href="{{ route('practice') }}" wire:navigate
               class="flex items-center justify-between rounded-2xl bg-linear-to-r from-primary-500 to-secondary-600 p-6 text-white shadow-lg transition hover:from-primary-600 hover:to-secondary-700">
                <div>
                    <h2 class="text-xl font-bold">Mode Latihan 🎯</h2>
                    <p class="mt-1 text-sm text-primary-50">Lawan AI untuk berlatih.</p>
                </div>
                <span class="rounded-lg bg-white/20 px-4 py-2 text-sm font-semibold">Main &rarr;</span>
            </a>

            <a href="{{ route('quick-play') }}" wire:navigate
               class="flex items-center justify-between rounded-2xl bg-linear-to-r from-secondary-500 to-primary-600 p-6 text-white shadow-lg transition hover:from-secondary-600 hover:to-primary-700">
                <div>
                    <h2 class="text-xl font-bold">Quick Play ⚔️</h2>
                    <p class="mt-1 text-sm text-secondary-50">Lawan pemain lain secara langsung.</p>
                </div>
                <span class="rounded-lg bg-white/20 px-4 py-2 text-sm font-semibold">Main &rarr;</span>
            </a>
        </div>

        <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
            <div class="rounded-xl bg-white p-4 shadow-xs">
                <p class="text-xs uppercase tracking-wide text-gray-500">Level</p>
                <p class="mt-1 text-2xl font-bold text-primary-600">{{ auth()->user()->level }}</p>
            </div>
            <div class="rounded-xl bg-white p-4 shadow-xs">
                <p class="text-xs uppercase tracking-wide text-gray-500">XP</p>
                <p class="mt-1 text-2xl font-bold text-primary-600">{{ number_format(auth()->user()->xp) }}</p>
            </div>
            <div class="rounded-xl bg-white p-4 shadow-xs">
                <p class="text-xs uppercase tracking-wide text-gray-500">Koin</p>
                <p class="mt-1 text-2xl font-bold text-secondary-600">{{ number_format(auth()->user()->coins) }}</p>
            </div>
            <div class="rounded-xl bg-white p-4 shadow-xs">
                <p class="text-xs uppercase tracking-wide text-gray-500">Peringkat</p>
                <p class="mt-1 text-2xl font-bold capitalize text-gray-900">{{ auth()->user()->rank }}</p>
            </div>
        </div>
    </div>
</x-layouts.app>
