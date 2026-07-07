@php
    $puzzle = $this->puzzle;
    $attempt = $this->attempt;
    $board = $puzzle->puzzle_config['board'];
    $solutionCells = $puzzle->puzzle_config['solution_cells'];
    $done = $attempt?->is_completed ?? false;
@endphp

<div class="mx-auto max-w-2xl px-4 py-8">
    {{-- Header --}}
    <div class="rounded-2xl bg-linear-to-r from-primary-600 to-secondary-600 p-6 text-white">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <h1 class="text-2xl font-bold">🧩 Puzzle Harian</h1>
                <p class="mt-0.5 text-sm text-white/80">{{ $puzzle->puzzle_date->format('d M Y') }} · {{ $puzzle->category->name ?? '' }}</p>
            </div>
            <div class="text-right">
                <div class="text-lg font-bold">{{ $puzzle->difficulty->label() }}</div>
                <div class="text-sm text-white/80">{{ $puzzle->xp_reward }} XP · 🪙 {{ $puzzle->coins_reward }}</div>
            </div>
        </div>
        <p class="mt-3 text-sm font-medium text-white/90">{{ $puzzle->puzzle_config['description'] }}</p>
    </div>

    {{-- Intro / start --}}
    @if (! $attempt)
        <div class="mt-6 rounded-2xl bg-white p-6 text-center shadow-xs">
            <p class="text-gray-700">
                Klaim kotak yang tepat untuk melengkapi garis kemenangan <span class="font-black text-primary-600">X</span>.
                Setiap kotak dijaga satu pertanyaan — jawaban salah boleh dicoba lagi, tapi mengurangi skor.
            </p>
            <p class="mt-2 text-sm text-gray-500">
                Satu kesempatan per hari · Batas waktu {{ intdiv($puzzle->time_limit, 60) }} menit dimulai saat kamu menekan Mulai.
            </p>
            <button wire:click="start" class="mt-4 rounded-lg bg-primary-600 px-6 py-2.5 font-semibold text-white transition hover:bg-primary-700">
                Mulai Puzzle
            </button>
        </div>
    @endif

    {{-- Timer --}}
    @if ($attempt && ! $done && ! $expired)
        <div class="mt-4 flex justify-center"
             wire:key="puzzle-timer-{{ $attempt->id }}"
             x-data="{ left: {{ $remaining }} }"
             x-init="$nextTick(() => { const t = setInterval(() => { if (--left <= 0) { clearInterval(t); $wire.timedOut(); } }, 1000); })">
            <span class="rounded-full px-3 py-1 text-sm font-semibold"
                  :class="left <= 15 ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600'">
                ⏱ <span x-text="Math.floor(left / 60)"></span>:<span x-text="String(left % 60).padStart(2, '0')"></span>
            </span>
        </div>
    @endif

    {{-- Result banners --}}
    @if ($done)
        <div class="mt-6 rounded-xl bg-green-50 px-4 py-5 text-center ring-1 ring-green-200">
            <p class="text-lg font-bold text-green-800">Puzzle Selesai! 🎉</p>
            <div class="mt-3 flex flex-wrap justify-center gap-3 text-sm">
                <span class="rounded-full bg-white px-3 py-1 font-semibold text-gray-700">Skor: {{ number_format($attempt->score) }}</span>
                <span class="rounded-full bg-white px-3 py-1 font-semibold text-gray-700">⏱ {{ $attempt->time_taken }} dtk</span>
                <span class="rounded-full bg-white px-3 py-1 font-semibold text-gray-700">✓ {{ $attempt->correct_answers }}/{{ $attempt->moves_used }} jawaban</span>
            </div>
            <p class="mt-3 text-sm font-medium text-green-700">
                +{{ $attempt->xp_earned }} XP · +🪙 {{ $attempt->coins_earned }}
            </p>
            <p class="mt-1 text-xs text-gray-500">Puzzle baru tersedia besok!</p>
        </div>
    @elseif ($expired)
        <div class="mt-6 rounded-xl bg-red-50 px-4 py-5 text-center ring-1 ring-red-200">
            <p class="text-lg font-bold text-red-800">⏱ Waktu Habis!</p>
            <p class="mt-1 text-sm text-red-700">Puzzle hari ini berakhir tanpa hadiah. Coba lagi besok!</p>
        </div>
    @endif

    {{-- Board --}}
    @if ($attempt)
        <div class="mt-6 grid grid-cols-3 gap-2" wire:key="puzzle-board-{{ $attempt->id }}">
            @foreach ($board as $i => $cell)
                @php
                    $mark = $cell ?? (in_array($i, $claimedCells, true) ? 'X' : '');
                    $isTarget = $targetCell === $i;
                    $isSolution = in_array($i, $solutionCells, true);
                @endphp
                <div class="flex aspect-square items-center justify-center rounded-xl border-4 text-5xl font-black transition
                        {{ $isTarget ? 'animate-pulse border-primary-500 bg-primary-50' : ($isSolution && $mark === '' ? 'border-primary-200 bg-white' : 'border-gray-200 bg-white') }}
                        {{ $mark === 'X' ? 'text-primary-600' : 'text-secondary-600' }}">
                    {{ $mark }}
                </div>
            @endforeach
        </div>
    @endif

    {{-- Question --}}
    @if ($question)
        <div class="mt-6 rounded-2xl bg-white p-6 shadow-xs" wire:key="puzzle-question-{{ $question->id }}-{{ $attempt->moves_used }}">
            <div class="flex items-center justify-between">
                <span class="rounded-full bg-primary-100 px-3 py-1 text-xs font-medium text-primary-800">
                    Soal {{ count($claimedCells) + 1 }} dari {{ count($solutionCells) }}
                </span>
                @if ($feedback === 'correct')
                    <span class="text-sm font-semibold text-green-600">Benar! ✓</span>
                @elseif ($feedback === 'wrong')
                    <span class="text-sm font-semibold text-red-600">Salah, coba lagi ✗</span>
                @endif
            </div>

            <p class="mt-3 text-lg font-semibold text-gray-900">{{ $question->question }}</p>
            <p class="mt-1 text-sm text-gray-500">Jawab dengan benar untuk mengklaim kotak yang berkedip.</p>

            <div class="mt-4 grid gap-2 sm:grid-cols-2">
                @foreach ($question->answers as $answerOption)
                    <button
                        wire:click="answer({{ $answerOption->id }})"
                        class="rounded-lg border-2 border-gray-200 px-4 py-3 text-left text-gray-800 transition hover:border-primary-400 hover:bg-primary-50">
                        {{ $answerOption->answer }}
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Today's ranking --}}
    <div class="mt-8 rounded-2xl bg-white p-6 shadow-xs">
        <h2 class="text-lg font-bold text-gray-900">Peringkat Hari Ini</h2>

        @if ($ranking->isEmpty())
            <p class="mt-3 text-sm text-gray-500">Belum ada yang menyelesaikan puzzle hari ini. Jadilah yang pertama!</p>
        @else
            <ol class="mt-3 space-y-2">
                @foreach ($ranking as $entry)
                    <li class="flex items-center justify-between rounded-lg px-3 py-2 text-sm
                            {{ $entry->user_id === auth()->id() ? 'bg-primary-50 ring-1 ring-primary-200' : 'bg-gray-50' }}">
                        <span class="flex items-center gap-3">
                            <span class="w-6 text-center font-bold text-gray-700">
                                {{ ['🥇', '🥈', '🥉'][$loop->index] ?? $loop->iteration }}
                            </span>
                            <span class="font-medium text-gray-800">{{ $entry->user->name }}</span>
                        </span>
                        <span class="flex items-center gap-3 text-gray-600">
                            <span>⏱ {{ $entry->time_taken }}s</span>
                            <span class="font-semibold text-gray-800">{{ number_format($entry->score) }}</span>
                        </span>
                    </li>
                @endforeach
            </ol>
        @endif
    </div>
</div>
