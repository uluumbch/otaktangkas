{{-- Decorative CSS confetti; parent needs `relative overflow-hidden`. --}}
<div class="pointer-events-none absolute inset-x-0 top-0 h-full" aria-hidden="true">
    @foreach ([8, 20, 33, 45, 58, 70, 82, 93, 27, 64] as $i => $left)
        <span class="animate-confetti absolute top-0 block size-2 {{ $i % 2 ? 'rounded-full' : 'rounded-xs' }}"
              style="left: {{ $left }}%;
                     background: {{ ['#fbbf24', '#38bdf8', '#e879f9', '#4ade80', '#f87171'][$i % 5] }};
                     animation-delay: {{ ($i % 5) * 0.18 }}s;"></span>
    @endforeach
</div>
