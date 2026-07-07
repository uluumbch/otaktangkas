{{-- Match VS header. Expects: $p1Name, $p2Name, $p1Symbol, $p2Symbol, $p1Active, $p2Active. --}}
<div class="mt-5 flex items-start justify-center gap-5 sm:gap-8">
    <div class="flex w-24 flex-col items-center gap-1.5">
        <span class="relative flex size-14 items-center justify-center rounded-full bg-linear-to-br from-primary-400 to-primary-600 text-xl font-black text-white
            {{ $p1Active ? 'animate-pulse-glow text-primary-500 ring-4 ring-primary-300' : 'opacity-80' }}">
            <span class="text-white">{{ mb_strtoupper(mb_substr($p1Name, 0, 1)) }}</span>
            <span class="absolute -right-1 -bottom-1 flex size-6 items-center justify-center rounded-full bg-white text-xs font-black text-primary-600 shadow-xs ring-1 ring-black/10">
                {{ $p1Symbol }}
            </span>
        </span>
        <span class="max-w-full truncate text-sm font-bold {{ $p1Active ? 'text-primary-700' : 'text-gray-500' }}">{{ $p1Name }}</span>
    </div>

    <span class="mt-3 -rotate-6 text-2xl font-black text-gray-300 italic select-none">VS</span>

    <div class="flex w-24 flex-col items-center gap-1.5">
        <span class="relative flex size-14 items-center justify-center rounded-full bg-linear-to-br from-secondary-400 to-secondary-600 text-xl font-black text-white
            {{ $p2Active ? 'animate-pulse-glow text-secondary-500 ring-4 ring-secondary-300' : 'opacity-80' }}">
            <span class="text-white">{{ mb_strtoupper(mb_substr($p2Name, 0, 1)) }}</span>
            <span class="absolute -right-1 -bottom-1 flex size-6 items-center justify-center rounded-full bg-white text-xs font-black text-secondary-600 shadow-xs ring-1 ring-black/10">
                {{ $p2Symbol }}
            </span>
        </span>
        <span class="max-w-full truncate text-sm font-bold {{ $p2Active ? 'text-secondary-700' : 'text-gray-500' }}">{{ $p2Name }}</span>
    </div>
</div>
