@php
    $match = $this->match;
    $board = $match->board_state ?? [['', '', ''], ['', '', ''], ['', '', '']];
    $status = $match->status->value;
    $myTurn = $this->isMyTurn();
    $question = $match->currentQuestion;
    $over = in_array($status, ['completed', 'abandoned'], true);
    $remaining = ($status === 'in_progress' && $match->turn_started_at)
        ? max(0, $match->turn_time_limit - (int) $match->turn_started_at->diffInSeconds(now()))
        : $match->turn_time_limit;
@endphp

<div class="mx-auto max-w-2xl px-4 py-8">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h1 class="text-lg font-bold text-gray-900">Mode Latihan</h1>
        <button wire:click="newGame" class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
            Game Baru
        </button>
    </div>

    {{-- Players / turn indicator --}}
    <div class="mt-4 flex items-center justify-center gap-4 text-sm">
        <span class="flex items-center gap-2 rounded-full px-3 py-1 {{ $myTurn ? 'bg-primary-100 text-primary-800 ring-2 ring-primary-400' : 'bg-gray-100 text-gray-600' }}">
            <span class="text-lg font-black text-primary-600">X</span> {{ $match->player1->name }}
        </span>
        <span class="text-gray-400">vs</span>
        <span class="flex items-center gap-2 rounded-full px-3 py-1 {{ ! $myTurn && $status === 'in_progress' ? 'bg-secondary-100 text-secondary-800 ring-2 ring-secondary-400' : 'bg-gray-100 text-gray-600' }}">
            <span class="text-lg font-black text-secondary-600">O</span> AI
        </span>
    </div>

    {{-- Turn timer (player's turn only) --}}
    @if ($status === 'in_progress' && $myTurn)
        <div class="mt-3 flex justify-center"
             wire:key="timer-{{ $match->id }}-{{ $match->current_question_id }}"
             x-data="{ left: {{ $remaining }} }"
             x-init="$nextTick(() => { const t = setInterval(() => { if (--left <= 0) { clearInterval(t); $wire.timeout(); } }, 1000); })">
            <span class="rounded-full px-3 py-1 text-sm font-semibold"
                  :class="left <= 5 ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600'">
                ⏱ <span x-text="left"></span> dtk
            </span>
        </div>
    @endif

    {{-- Result banner --}}
    @if ($over)
        @php
            $banner = match ($match->result) {
                'player1_win' => ['Kamu Menang! 🎉', 'bg-green-50 text-green-800 ring-green-200'],
                'player2_win' => ['Kamu Kalah 😔', 'bg-red-50 text-red-800 ring-red-200'],
                default => ['Seri 🤝', 'bg-yellow-50 text-yellow-800 ring-yellow-200'],
            };
        @endphp
        <div class="mt-6 rounded-xl px-4 py-4 text-center text-lg font-bold ring-1 {{ $banner[1] }}">
            @if ($status === 'abandoned')<p class="text-sm font-medium text-red-600">⏱ Waktu habis!</p>@endif
            {{ $banner[0] }}
            <div class="mt-3">
                <button wire:click="newGame" class="rounded-lg bg-primary-600 px-5 py-2 text-sm font-semibold text-white hover:bg-primary-700">
                    Main Lagi
                </button>
            </div>
        </div>
    @endif

    {{-- AI thinking: after the player's move lands, pause briefly then let the AI play. --}}
    @if ($status === 'in_progress' && ! $myTurn)
        <div class="mt-4 flex items-center justify-center gap-2 text-sm font-medium text-secondary-600"
             wire:key="ai-thinking-{{ $match->id }}-{{ $match->current_question_id }}"
             x-data
             x-init="setTimeout(() => $wire.aiTurn(), 900)">
            <svg class="size-4 animate-spin" width="16" height="16" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.4 0 0 5.4 0 12h4z"></path>
            </svg>
            AI sedang berpikir…
        </div>
    @endif

    {{-- Board --}}
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
    @error('board') <p class="mt-2 text-center text-sm text-red-600">{{ $message }}</p> @enderror

    {{-- Question --}}
    @if ($status === 'in_progress' && $question)
        <div class="mt-6 rounded-2xl bg-white p-6 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="rounded-full bg-primary-100 px-3 py-1 text-xs font-medium text-primary-800">
                    {{ $question->category->name ?? 'Pertanyaan' }}
                </span>
                @if ($feedback === 'correct')
                    <span class="text-sm font-semibold text-green-600">Benar! ✓</span>
                @elseif ($feedback === 'wrong')
                    <span class="text-sm font-semibold text-red-600">Salah, coba lagi ✗</span>
                @endif
            </div>

            <p class="mt-3 text-lg font-semibold text-gray-900">{{ $question->question }}</p>

            @unless ($myTurn)
                <p class="mt-2 text-sm text-gray-500">Menunggu giliran lawan…</p>
            @else
                <p class="mt-1 text-sm text-gray-500">
                    {{ $selectedPosition ? 'Kotak dipilih — pilih jawaban yang benar.' : 'Pilih kotak di papan, lalu jawab.' }}
                </p>
            @endunless

            <div class="mt-4 grid gap-2 sm:grid-cols-2">
                @foreach ($question->answers as $answer)
                    <button
                        wire:click="answer({{ $answer->id }})"
                        @disabled(! $myTurn)
                        class="rounded-lg border-2 border-gray-200 px-4 py-3 text-left text-gray-800 transition hover:border-primary-400 hover:bg-primary-50 disabled:cursor-not-allowed disabled:opacity-50">
                        {{ $answer->answer }}
                    </button>
                @endforeach
            </div>
        </div>
    @endif
</div>
