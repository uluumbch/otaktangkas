{{-- 6x7 Connect Four board; a move selects a whole column.
     Expects: $match, $board, $myTurn, $selectedPosition. --}}
<div class="shadow-game mt-6 rounded-3xl bg-linear-to-b from-primary-600 to-primary-800 p-2 ring-4 ring-primary-800/60 sm:p-3"
     wire:key="board-{{ $match->id }}">
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
                class="flex flex-col gap-1 overflow-hidden rounded-xl p-1 transition sm:gap-2
                    {{ $selected ? 'bg-white/25 ring-2 ring-white/80' : '' }}
                    {{ $myTurn && $open ? 'cursor-pointer hover:bg-white/15' : 'cursor-not-allowed' }}">
                @for ($r = 0; $r < 6; $r++)
                    @php $cell = $board[$r][$c] ?? ''; @endphp
                    @if ($cell === '')
                        <span class="block aspect-square w-full rounded-full bg-primary-900/50 shadow-[inset_0_2px_6px_rgb(0_0_0/0.45)]"></span>
                    @else
                        {{-- Value-keyed so a newly landed disc falls in exactly once. --}}
                        <span wire:key="disc-{{ $r }}-{{ $c }}-{{ $cell }}"
                              class="animate-disc-drop block aspect-square w-full rounded-full shadow-[inset_0_-3px_5px_rgb(0_0_0/0.3)] ring-2
                                {{ $cell === 'X'
                                    ? 'bg-[radial-gradient(circle_at_32%_28%,var(--color-primary-200),var(--color-primary-500)_60%)] ring-primary-300/70'
                                    : 'bg-[radial-gradient(circle_at_32%_28%,var(--color-secondary-200),var(--color-secondary-500)_60%)] ring-secondary-300/70' }}"></span>
                    @endif
                @endfor
            </button>
        @endfor
    </div>
</div>
