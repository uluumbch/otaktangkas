@php
    $match = $this->match;
    $board = $match->board_state ?? [];
    $unit = $match->game_type === 'connect_four' ? 'kolom' : 'kotak';
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
        <button wire:click="newGame" class="btn-game-light px-3 py-1.5 text-sm">
            Game Baru
        </button>
    </div>

    {{-- Game type picker --}}
    <div class="mt-4 flex justify-center gap-2 text-sm" role="group" aria-label="Pilih permainan">
        @foreach (['tic_tac_toe' => '⭕ Tic-Tac-Toe', 'connect_four' => '🔴 Empat Sejajar'] as $type => $label)
            <button
                wire:click="setGameType('{{ $type }}')"
                class="cursor-pointer rounded-full px-4 py-1.5 font-bold transition
                    {{ $gameType === $type
                        ? 'shadow-game bg-linear-to-r from-primary-500 to-secondary-500 text-white'
                        : 'bg-white text-gray-600 ring-1 ring-gray-300 hover:-translate-y-0.5 hover:bg-gray-50' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Players / turn indicator --}}
    @include('livewire.partials.vs-header', [
        'p1Name' => $match->player1->name,
        'p2Name' => 'AI',
        'p1Symbol' => $match->player1_symbol,
        'p2Symbol' => $match->player2_symbol,
        'p1Active' => $myTurn,
        'p2Active' => ! $myTurn && $status === 'in_progress',
    ])

    {{-- Turn timer (player's turn only) --}}
    @if ($status === 'in_progress' && $myTurn)
        <div class="mt-3 flex justify-center"
             wire:key="timer-{{ $match->id }}-{{ $match->current_question_id }}"
             x-data="{ left: {{ $remaining }} }"
             x-init="$nextTick(() => { const t = setInterval(() => { if (--left <= 0) { clearInterval(t); $wire.timeout(); } }, 1000); })">
            <span class="rounded-full px-3 py-1 text-sm font-bold shadow-xs ring-1 ring-black/5"
                  :class="left <= 5 ? 'bg-red-100 text-red-700 animate-pulse' : 'bg-white text-gray-600'">
                ⏱ <span x-text="left"></span> dtk
            </span>
        </div>
    @endif

    {{-- Result banner --}}
    @if ($over)
        @php $iWon = $match->result === 'player1_win'; @endphp
        @if ($iWon)
            <div class="shadow-game relative mt-6 overflow-hidden rounded-2xl bg-linear-to-br from-primary-500 to-secondary-600 px-4 py-6 text-center text-white">
                @include('livewire.partials.confetti')
                <div class="animate-float text-5xl">🏆</div>
                <p class="animate-pop-in mt-2 text-2xl font-black tracking-tight">Kamu Menang! 🎉</p>
                @if ($status === 'completed')
                    <div class="mt-3 flex justify-center gap-2 text-sm">
                        <span class="chip-hud bg-white/20 text-white">+{{ (int) ($match->xp_awarded * 1.5) }} XP</span>
                        <span class="chip-hud bg-white/20 text-white">🪙 +{{ (int) ($match->coins_awarded * 1.5) }}</span>
                    </div>
                @endif
                <button wire:click="newGame" class="btn-game mt-4 border-white/40 bg-white/20 text-sm hover:bg-white/30">
                    Main Lagi
                </button>
            </div>
        @else
            @php
                $banner = $match->result === 'player2_win'
                    ? ['😔', 'Kamu Kalah', 'Jangan menyerah — coba lagi!']
                    : ['🤝', 'Seri', 'Ketat! Sekali lagi?'];
            @endphp
            <div class="shadow-game mt-6 rounded-2xl bg-white px-4 py-6 text-center">
                @if ($status === 'abandoned')<p class="text-sm font-bold text-red-600">⏱ Waktu habis!</p>@endif
                <div class="text-5xl">{{ $banner[0] }}</div>
                <p class="mt-2 text-2xl font-black tracking-tight text-gray-900">{{ $banner[1] }}</p>
                <p class="mt-1 text-sm text-gray-500">{{ $banner[2] }}</p>
                <button wire:click="newGame" class="btn-game mt-4 text-sm">
                    Main Lagi
                </button>
            </div>
        @endif
    @endif

    {{-- AI thinking: after the player's move lands, pause briefly then let the AI play. --}}
    @if ($status === 'in_progress' && ! $myTurn)
        <div class="mt-4 flex items-center justify-center gap-2"
             wire:key="ai-thinking-{{ $match->id }}-{{ $match->current_question_id }}"
             x-data
             x-init="setTimeout(() => $wire.aiTurn(), 900)">
            <span class="flex size-8 items-center justify-center rounded-full bg-linear-to-br from-secondary-400 to-secondary-600 text-sm font-black text-white">A</span>
            <span class="flex items-center gap-1.5 rounded-2xl rounded-bl-xs bg-white px-3 py-2 text-sm font-bold text-secondary-700 shadow-xs ring-1 ring-black/5">
                AI sedang berpikir
                <span class="flex gap-0.5">
                    <span class="size-1 animate-bounce rounded-full bg-secondary-400" style="animation-delay: 0ms"></span>
                    <span class="size-1 animate-bounce rounded-full bg-secondary-400" style="animation-delay: 150ms"></span>
                    <span class="size-1 animate-bounce rounded-full bg-secondary-400" style="animation-delay: 300ms"></span>
                </span>
            </span>
        </div>
    @endif

    {{-- Board (per game type) --}}
    @include('livewire.partials.board-'.str_replace('_', '-', $match->game_type))
    @error('board') <p class="mt-2 text-center text-sm text-red-600">{{ $message }}</p> @enderror

    {{-- Question --}}
    @if ($status === 'in_progress' && $question)
        @php $shakeKey = $match->moves()->count(); @endphp
        <div class="shadow-game mt-6 rounded-2xl bg-white p-6 {{ $feedback === 'wrong' ? 'animate-shake' : '' }}"
             wire:key="qcard-{{ $match->id }}-{{ $shakeKey }}">
            <div class="flex items-center justify-between">
                <span class="rounded-full bg-primary-100 px-3 py-1 text-xs font-bold text-primary-800">
                    {{ $question->category->name ?? 'Pertanyaan' }}
                </span>
                @if ($feedback === 'correct')
                    <span class="animate-pop-in text-sm font-black text-green-600">Benar! ✓</span>
                @elseif ($feedback === 'wrong')
                    <span class="text-sm font-black text-red-600">Salah, coba lagi ✗</span>
                @endif
            </div>

            <p class="mt-3 text-lg font-semibold text-gray-900">{{ $question->question }}</p>

            @unless ($myTurn)
                <p class="mt-2 text-sm text-gray-500">Menunggu giliran lawan…</p>
            @else
                <p class="mt-1 text-sm text-gray-500">
                    {{ $selectedPosition ? ucfirst($unit).' dipilih — pilih jawaban yang benar.' : "Pilih {$unit} di papan, lalu jawab." }}
                </p>
            @endunless

            <div class="mt-4 grid gap-2 sm:grid-cols-2">
                @foreach ($question->answers as $answer)
                    <button
                        wire:click="answer({{ $answer->id }})"
                        @disabled(! $myTurn)
                        class="rounded-xl border-2 border-b-4 border-gray-200 px-4 py-3 text-left font-semibold text-gray-800 transition hover:-translate-y-0.5 hover:border-primary-400 hover:bg-primary-50 active:translate-y-0 disabled:cursor-not-allowed disabled:opacity-50">
                        {{ $answer->answer }}
                    </button>
                @endforeach
            </div>
        </div>
    @endif
</div>
