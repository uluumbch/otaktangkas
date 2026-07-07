<div class="mx-auto max-w-2xl px-4 py-8">
    @php
        $unlocked = $this->unlocked;
        $rarityRing = [
            'common' => 'ring-gray-200',
            'rare' => 'ring-blue-300',
            'epic' => 'ring-purple-300',
            'legendary' => 'ring-yellow-300',
        ];
    @endphp

    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">🎖️ Prestasi</h1>
        <span class="rounded-full bg-primary-100 px-3 py-1 text-sm font-medium text-primary-800">
            {{ $unlocked->count() }} / {{ $this->achievements->count() }}
        </span>
    </div>

    <div class="mt-6 grid gap-3 sm:grid-cols-2">
        @foreach ($this->achievements as $achievement)
            @php $isUnlocked = $unlocked->has($achievement->id); @endphp
            <div class="flex items-start gap-3 rounded-2xl bg-white p-4 shadow-xs ring-1 {{ $rarityRing[$achievement->rarity] ?? 'ring-gray-200' }} {{ $isUnlocked ? '' : 'opacity-60' }}">
                <div class="flex size-11 shrink-0 items-center justify-center rounded-xl text-2xl {{ $isUnlocked ? 'bg-primary-100' : 'bg-gray-100 grayscale' }}">
                    {{ $isUnlocked ? '🏅' : '🔒' }}
                </div>
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <h2 class="font-semibold text-gray-900">{{ $achievement->name }}</h2>
                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] uppercase tracking-wide text-gray-500">{{ $achievement->rarity }}</span>
                    </div>
                    <p class="mt-0.5 text-sm text-gray-600">{{ $achievement->description }}</p>
                    <p class="mt-1 text-xs font-medium text-gray-500">
                        +{{ $achievement->xp_reward }} XP · +{{ $achievement->coins_reward }} 🪙
                        @if ($isUnlocked)
                            <span class="text-green-600">· Terbuka ✓</span>
                        @endif
                    </p>
                </div>
            </div>
        @endforeach
    </div>
</div>
