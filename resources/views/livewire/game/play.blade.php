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
        <div class="mt-4 flex items-center justify-center gap-4 text-sm">
            <span class="flex items-center gap-2 rounded-full px-3 py-1 {{ $myTurn ? 'bg-primary-100 text-primary-800 ring-2 ring-primary-400' : 'bg-gray-100 text-gray-600' }}">
                <span class="text-lg font-black text-primary-600">{{ $mySymbol }}</span> Kamu
            </span>
            <span class="text-gray-400">vs</span>
            <span class="flex items-center gap-2 rounded-full px-3 py-1 {{ ! $myTurn && $status === 'in_progress' ? 'bg-secondary-100 text-secondary-800 ring-2 ring-secondary-400' : 'bg-gray-100 text-gray-600' }}">
                <span class="text-lg font-black text-secondary-600">{{ $mySymbol === 'X' ? 'O' : 'X' }}</span> {{ $opponent?->name ?? 'Lawan' }}
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
                $iWon = $match->winner_id === auth()->id();
                $banner = match (true) {
                    $match->result === 'draw' => ['Seri 🤝', 'bg-yellow-50 text-yellow-800 ring-yellow-200'],
                    $iWon => ['Kamu Menang! 🎉', 'bg-green-50 text-green-800 ring-green-200'],
                    default => ['Kamu Kalah 😔', 'bg-red-50 text-red-800 ring-red-200'],
                };
            @endphp
            <div class="mt-6 rounded-xl px-4 py-4 text-center text-lg font-bold ring-1 {{ $banner[1] }}">
                @if ($status === 'abandoned')<p class="text-sm font-medium text-red-600">⏱ Waktu habis atau lawan keluar</p>@endif
                {{ $banner[0] }}
                <div class="mt-3">
                    <a href="{{ route('quick-play') }}" wire:navigate class="inline-block rounded-lg bg-primary-600 px-5 py-2 text-sm font-semibold text-white hover:bg-primary-700">
                        Main Lagi
                    </a>
                </div>
            </div>
        @elseif (! $myTurn)
            <p class="mt-4 text-center text-sm font-medium text-gray-500">Menunggu giliran lawan…</p>
        @endif

        {{-- Board (per game type) --}}
        @include('livewire.partials.board-'.str_replace('_', '-', $match->game_type))
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
                <p class="mt-1 text-sm text-gray-500">
                    {{ $myTurn ? ($selectedPosition ? ucfirst($unit).' dipilih — pilih jawaban yang benar.' : "Pilih {$unit} di papan, lalu jawab.") : 'Menunggu giliran lawan…' }}
                </p>

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
    @endif
</div>
