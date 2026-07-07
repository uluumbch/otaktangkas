<div class="mx-auto max-w-lg px-4 py-10">
    <div class="shadow-game rounded-3xl bg-white p-8 text-center">
        <div class="animate-float mx-auto flex size-16 items-center justify-center rounded-2xl bg-linear-to-br from-primary-500 to-secondary-600 text-3xl">
            ⚔️
        </div>
        <h2 class="mt-4 text-2xl font-black tracking-tight text-gray-900">Lawan Pemain Lain</h2>
        <p class="mt-2 text-sm text-gray-600">
            Kami akan mencarikan lawan dengan level yang mirip. Jika belum ada,
            kamu akan menunggu sampai ada yang bergabung.
        </p>

        {{-- Game type picker: mini board previews --}}
        <div class="mt-6 grid grid-cols-3 gap-3" role="group" aria-label="Pilih permainan">
            {{-- Tic-Tac-Toe --}}
            <button
                wire:click="setGameType('tic_tac_toe')"
                class="cursor-pointer rounded-2xl p-4 transition hover:-translate-y-0.5
                    {{ $gameType === 'tic_tac_toe' ? 'shadow-game bg-primary-50 ring-4 ring-primary-400' : 'bg-gray-50 ring-1 ring-gray-200 hover:ring-primary-200' }}">
                <div class="mx-auto grid w-16 grid-cols-3 gap-0.5">
                    @foreach (['X', '', 'O', '', 'X', '', 'O', '', 'X'] as $cell)
                        <span class="flex aspect-square items-center justify-center rounded-xs bg-white text-[10px] font-black shadow-xs
                            {{ $cell === 'X' ? 'text-primary-500' : 'text-secondary-500' }}">{{ $cell }}</span>
                    @endforeach
                </div>
                <p class="mt-2 text-sm font-black {{ $gameType === 'tic_tac_toe' ? 'text-primary-700' : 'text-gray-600' }}">⭕ Tic-Tac-Toe</p>
            </button>

            {{-- Connect Four --}}
            <button
                wire:click="setGameType('connect_four')"
                class="cursor-pointer rounded-2xl p-4 transition hover:-translate-y-0.5
                    {{ $gameType === 'connect_four' ? 'shadow-game bg-primary-50 ring-4 ring-primary-400' : 'bg-gray-50 ring-1 ring-gray-200 hover:ring-primary-200' }}">
                <div class="mx-auto grid w-16 grid-cols-4 gap-0.5 rounded-sm bg-primary-700 p-1">
                    @foreach (['', '', '', '', '', 'X', 'O', '', 'X', 'O', 'X', 'O'] as $cell)
                        <span class="aspect-square rounded-full
                            {{ $cell === 'X' ? 'bg-primary-400' : ($cell === 'O' ? 'bg-secondary-400' : 'bg-primary-900/60') }}"></span>
                    @endforeach
                </div>
                <p class="mt-2 text-sm font-black {{ $gameType === 'connect_four' ? 'text-primary-700' : 'text-gray-600' }}">🔴 Empat Sejajar</p>
            </button>

            {{-- Memory Match --}}
            <button
                wire:click="setGameType('memory_match')"
                class="cursor-pointer rounded-2xl p-4 transition hover:-translate-y-0.5
                    {{ $gameType === 'memory_match' ? 'shadow-game bg-primary-50 ring-4 ring-primary-400' : 'bg-gray-50 ring-1 ring-gray-200 hover:ring-primary-200' }}">
                <div class="mx-auto grid w-16 grid-cols-4 gap-0.5">
                    @foreach (['', '🍎', '', '', '', '', '🍎', '', '', '', '', ''] as $cell)
                        <span class="flex aspect-square items-center justify-center rounded-xs text-[8px]
                            {{ $cell === '' ? 'bg-linear-to-br from-primary-400 to-secondary-500' : 'bg-white shadow-xs' }}">{{ $cell }}</span>
                    @endforeach
                </div>
                <p class="mt-2 text-sm font-black {{ $gameType === 'memory_match' ? 'text-primary-700' : 'text-gray-600' }}">🧠 Memory</p>
            </button>
        </div>

        <button
            wire:click="findMatch"
            wire:loading.attr="disabled"
            wire:target="findMatch"
            class="btn-game mt-6 w-full py-3 text-base">
            <span wire:loading.remove wire:target="findMatch">Cari Lawan</span>
            <span wire:loading wire:target="findMatch">Mencari…</span>
        </button>
    </div>
</div>
