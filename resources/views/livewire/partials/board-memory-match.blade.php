{{-- 4x4 Memory Match board; a move flips two cards ("a|b").
     Two-tap selection is handled client-side by Alpine: the first tap marks
     a card, the second submits the pair through the shared selectCell().
     Expects: $match, $board (state array), $myTurn, $selectedPosition. --}}
@php
    $cards = $board['cards'] ?? [];
    $matched = $board['matched'] ?? [];
    $lastFlip = $board['last_flip'] ?? null;
    $flipCards = $lastFlip['cards'] ?? [];
    $scores = $board['scores'] ?? [];
    $selPair = $selectedPosition ? array_map('intval', explode('|', $selectedPosition)) : [];
    $flipKey = implode('-', $flipCards);
@endphp

<div wire:key="board-{{ $match->id }}">
    {{-- Pair score chips --}}
    <div class="mt-5 flex items-center justify-center gap-3 text-sm">
        <span class="chip-hud bg-primary-100 text-primary-800">
            {{ $match->player1_symbol }} · {{ $scores[$match->player1_symbol] ?? 0 }} pasangan
        </span>
        <span class="chip-hud bg-secondary-100 text-secondary-800">
            {{ $match->player2_symbol }} · {{ $scores[$match->player2_symbol] ?? 0 }} pasangan
        </span>
    </div>

    <div class="mt-4 grid grid-cols-4 gap-2 sm:gap-3" x-data="{ first: null }">
        @foreach ($cards as $i => $value)
            @php
                $owner = $matched[$i] ?? null;
                $inFlip = in_array($i, $flipCards, true) && $owner === null;
                $faceUp = $owner !== null || $inFlip;
                $inSelection = in_array($i, $selPair, true);
            @endphp

            @if ($owner !== null)
                {{-- Claimed: stays face-up in the owner's color. --}}
                <div wire:key="mm-card-{{ $i }}-won-{{ $owner }}"
                     class="animate-pop-in shadow-game flex aspect-square items-center justify-center rounded-2xl bg-white text-3xl opacity-80 sm:text-4xl
                        {{ $owner === $match->player1_symbol ? 'ring-4 ring-primary-300' : 'ring-4 ring-secondary-300' }}">
                    {{ $value }}
                </div>
            @else
                {{-- Unclaimed: always selectable; shows its face while part
                     of the latest flip, otherwise the card back. --}}
                <button
                    type="button"
                    wire:key="mm-card-{{ $i }}-{{ $inFlip ? 'flip-'.$flipKey : 'back' }}"
                    @disabled(! $myTurn)
                    x-on:click="
                        first === {{ $i }}
                            ? first = null
                            : (first === null
                                ? first = {{ $i }}
                                : ($wire.selectCell(Math.min(first, {{ $i }}) + '|' + Math.max(first, {{ $i }})), first = null))
                    "
                    x-bind:class="first === {{ $i }} ? 'ring-4 ring-amber-400 -translate-y-0.5' : ''"
                    aria-label="Kartu {{ $i + 1 }}"
                    class="animate-pop-in shadow-game flex aspect-square items-center justify-center rounded-2xl transition
                        {{ $inFlip
                            ? 'bg-white text-3xl ring-4 ring-amber-400 sm:text-4xl'
                            : 'bg-linear-to-br from-primary-500 to-secondary-600 text-2xl text-white/90' }}
                        {{ $inSelection ? 'ring-4 ring-primary-400' : '' }}
                        {{ $myTurn ? 'cursor-pointer hover:-translate-y-0.5 hover:ring-2 hover:ring-primary-300' : 'cursor-not-allowed opacity-90' }}">
                    {{ $inFlip ? $value : '🧠' }}
                </button>
            @endif
        @endforeach
    </div>

    @if ($lastFlip && ! ($lastFlip['matched'] ?? false))
        <p class="mt-3 text-center text-xs font-bold text-amber-600">
            Kartu berkedip kuning baru saja dibuka — ingat posisinya!
        </p>
    @endif
</div>
