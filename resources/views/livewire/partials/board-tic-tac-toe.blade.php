{{-- 3x3 Tic-Tac-Toe board. Expects: $match, $board, $myTurn, $selectedPosition. --}}
<div class="mt-6 grid grid-cols-3 gap-2 sm:gap-3" wire:key="board-{{ $match->id }}">
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
                class="shadow-game flex aspect-square items-center justify-center rounded-2xl text-5xl font-black transition sm:text-6xl
                    {{ $selected ? 'bg-primary-50 ring-4 ring-primary-400' : 'bg-white ring-1 ring-black/5' }}
                    {{ $myTurn && $isEmpty ? 'cursor-pointer hover:-translate-y-0.5 hover:ring-2 hover:ring-primary-300' : 'cursor-not-allowed' }}">
                @if (! $isEmpty)
                    {{-- Value-keyed so a newly placed symbol pops in exactly once. --}}
                    <span wire:key="mark-{{ $pos }}-{{ $cell }}"
                          class="animate-pop-in {{ $cell === 'X' ? 'text-primary-500' : 'text-secondary-500' }}"
                          style="text-shadow: 0 3px 0 color-mix(in oklab, currentColor 35%, transparent);">
                        {{ $cell }}
                    </span>
                @endif
            </button>
        @endfor
    @endfor
</div>
