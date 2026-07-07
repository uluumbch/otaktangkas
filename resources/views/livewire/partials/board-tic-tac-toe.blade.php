{{-- 3x3 Tic-Tac-Toe board. Expects: $match, $board, $myTurn, $selectedPosition. --}}
<div class="mt-6 grid grid-cols-3 gap-2" wire:key="board-{{ $match->id }}">
    @for ($r = 0; $r < 3; $r++)
        @for ($c = 0; $c < 3; $c++)
            @php
                $pos = "$r,$c";
                $cell = $board[$r][$c] ?? '';
                $isEmpty = $cell === '';
                $selected = $selectedPosition === $pos;
            @endphp
            <button
                wire:click="selectCell('{{ $pos }}')"
                @disabled(! $myTurn || ! $isEmpty)
                class="flex aspect-square items-center justify-center rounded-xl border-4 text-5xl font-black transition
                    {{ $selected ? 'border-primary-500 bg-primary-50' : 'border-gray-200 bg-white' }}
                    {{ $myTurn && $isEmpty ? 'cursor-pointer hover:border-primary-300' : 'cursor-not-allowed' }}
                    {{ $cell === 'X' ? 'text-primary-600' : 'text-secondary-600' }}">
                {{ $cell }}
            </button>
        @endfor
    @endfor
</div>
