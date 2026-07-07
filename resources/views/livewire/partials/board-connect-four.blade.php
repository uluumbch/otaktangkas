{{-- 6x7 Connect Four board; a move selects a whole column.
     Expects: $match, $board, $myTurn, $selectedPosition. --}}
<div class="mt-6 rounded-2xl bg-gray-200 p-2 sm:p-3" wire:key="board-{{ $match->id }}">
    <div class="grid grid-cols-7 gap-1 sm:gap-2">
        @for ($c = 0; $c < 7; $c++)
            @php
                $open = ($board[0][$c] ?? '') === '';
                $selected = $selectedPosition === (string) $c;
            @endphp
            <button
                type="button"
                wire:click="selectCell('{{ $c }}')"
                @disabled(! $myTurn || ! $open)
                aria-label="Kolom {{ $c + 1 }}"
                class="flex flex-col gap-1 rounded-lg p-1 transition sm:gap-2
                    {{ $selected ? 'bg-primary-100 ring-2 ring-primary-500' : '' }}
                    {{ $myTurn && $open ? 'cursor-pointer hover:bg-primary-50' : 'cursor-not-allowed' }}">
                @for ($r = 0; $r < 6; $r++)
                    @php $cell = $board[$r][$c] ?? ''; @endphp
                    <span class="block aspect-square w-full rounded-full border-2 transition
                        {{ $cell === 'X' ? 'border-primary-700 bg-primary-500' : ($cell === 'O' ? 'border-secondary-700 bg-secondary-500' : 'border-gray-300 bg-white') }}"></span>
                @endfor
            </button>
        @endfor
    </div>
</div>
