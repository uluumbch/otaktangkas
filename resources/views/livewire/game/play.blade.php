@php
    $match = $this->match;
    $board = $match->board_state ?? [];
    $unit = $match->game_type === 'connect_four' ? 'kolom' : 'kotak';
    $status = $match->status->value;
    $myTurn = $this->isMyTurn();
    $mySymbol = $this->mySymbol();
    $question = $match->currentQuestion;
    $opponent = auth()->id() === $match->player1_id ? $match->player2 : $match->player1;
    $needsPoll = $status === 'waiting' || ($status === 'in_progress' && ! $myTurn);
    $over = in_array($status, ['completed', 'abandoned'], true);
    $remaining = ($status === 'in_progress' && $match->turn_started_at)
        ? max(0, $match->turn_time_limit - (int) $match->turn_started_at->diffInSeconds(now()))
        : $match->turn_time_limit;
@endphp

<div class="mx-auto max-w-2xl px-4 py-8" @if ($needsPoll) wire:poll.2s="refresh" @endif>
    {{-- Waiting for an opponent --}}
    @if ($status === 'waiting')
        <div class="mt-10 rounded-2xl bg-white p-8 text-center shadow-xs">
            <svg class="mx-auto size-8 animate-spin text-primary-500" width="32" height="32" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.4 0 0 5.4 0 12h4z"></path>
            </svg>
            <p class="mt-4 font-semibold text-gray-900">Menunggu lawan bergabung…</p>
            <p class="mt-1 text-sm text-gray-500">Kode pertandingan: <span class="font-mono font-bold">{{ $match->match_code }}</span></p>
        </div>
    @else
        {{-- Players / turn indicator --}}
        @include('livewire.partials.vs-header', [
            'p1Name' => 'Kamu',
            'p2Name' => $opponent?->name ?? 'Lawan',
            'p1Symbol' => $mySymbol,
            'p2Symbol' => $mySymbol === 'X' ? 'O' : 'X',
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
            @php $iWon = $match->winner_id === auth()->id(); @endphp
            @if ($iWon && $match->result !== 'draw')
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
                    <a href="{{ route('quick-play') }}" wire:navigate class="btn-game mt-4 border-white/40 bg-white/20 text-sm hover:bg-white/30">
                        Main Lagi
                    </a>
                </div>
            @else
                @php
                    $banner = $match->result === 'draw'
                        ? ['🤝', 'Seri', 'Ketat! Sekali lagi?']
                        : ['😔', 'Kamu Kalah', 'Jangan menyerah — coba lagi!'];
                @endphp
                <div class="shadow-game mt-6 rounded-2xl bg-white px-4 py-6 text-center">
                    @if ($status === 'abandoned')<p class="text-sm font-bold text-red-600">⏱ Waktu habis atau lawan keluar</p>@endif
                    <div class="text-5xl">{{ $banner[0] }}</div>
                    <p class="mt-2 text-2xl font-black tracking-tight text-gray-900">{{ $banner[1] }}</p>
                    <p class="mt-1 text-sm text-gray-500">{{ $banner[2] }}</p>
                    <a href="{{ route('quick-play') }}" wire:navigate class="btn-game mt-4 text-sm">
                        Main Lagi
                    </a>
                </div>
            @endif
        @elseif (! $myTurn)
            <p class="mt-4 text-center text-sm font-bold text-gray-500">Menunggu giliran lawan…</p>
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
                <p class="mt-1 text-sm text-gray-500">
                    {{ $myTurn ? ($selectedPosition ? ucfirst($unit).' dipilih — pilih jawaban yang benar.' : "Pilih {$unit} di papan, lalu jawab.") : 'Menunggu giliran lawan…' }}
                </p>

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
    @endif
</div>
