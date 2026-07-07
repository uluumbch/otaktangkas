<div class="mx-auto max-w-lg px-4 py-10">
    <div class="rounded-2xl bg-white p-8 text-center shadow-xs">
        <div class="mx-auto flex size-16 items-center justify-center rounded-2xl bg-linear-to-br from-primary-500 to-secondary-600 text-3xl">
            ⚔️
        </div>
        <h2 class="mt-4 text-xl font-bold text-gray-900">Lawan Pemain Lain</h2>
        <p class="mt-2 text-sm text-gray-600">
            Kami akan mencarikan lawan dengan level yang mirip. Jika belum ada,
            kamu akan menunggu sampai ada yang bergabung.
        </p>

        {{-- Game type picker --}}
        <div class="mt-5 flex justify-center gap-2 text-sm" role="group" aria-label="Pilih permainan">
            @foreach (['tic_tac_toe' => '⭕ Tic-Tac-Toe', 'connect_four' => '🔴 Empat Sejajar'] as $type => $label)
                <button
                    wire:click="setGameType('{{ $type }}')"
                    class="rounded-full px-4 py-1.5 font-medium transition
                        {{ $gameType === $type
                            ? 'bg-primary-600 text-white shadow-xs'
                            : 'bg-white text-gray-600 ring-1 ring-gray-300 hover:bg-gray-50' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <button
            wire:click="findMatch"
            wire:loading.attr="disabled"
            wire:target="findMatch"
            class="mt-6 inline-flex w-full items-center justify-center gap-2 rounded-lg bg-primary-600 px-5 py-3 font-semibold text-white transition hover:bg-primary-700 disabled:opacity-60">
            <span wire:loading.remove wire:target="findMatch">Cari Lawan</span>
            <span wire:loading wire:target="findMatch">Mencari…</span>
        </button>
    </div>
</div>
