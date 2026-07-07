<div class="mx-auto max-w-2xl px-4 py-8">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">🏆 Peringkat</h1>
        <span class="rounded-full bg-primary-100 px-3 py-1 text-sm font-medium text-primary-800">
            Posisimu: #{{ $this->myPosition }}
        </span>
    </div>

    <div class="mt-6 overflow-hidden rounded-2xl bg-white shadow-xs">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-gray-100 text-xs uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-4 py-3">#</th>
                    <th class="px-4 py-3">Pemain</th>
                    <th class="hidden px-4 py-3 sm:table-cell">Rank</th>
                    <th class="px-4 py-3 text-right">Menang</th>
                    <th class="px-4 py-3 text-right">XP</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($this->players as $i => $player)
                    @php $position = $i + 1; @endphp
                    <tr class="{{ $player->id === auth()->id() ? 'bg-primary-50' : '' }}">
                        <td class="px-4 py-3 font-bold text-gray-700">
                            {{ ['🥇', '🥈', '🥉'][$i] ?? $position }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="font-semibold text-gray-900">{{ $player->name }}</span>
                            <span class="block text-xs text-gray-500">Lv {{ $player->level }}</span>
                        </td>
                        <td class="hidden px-4 py-3 capitalize text-gray-600 sm:table-cell">{{ $player->rank }}</td>
                        <td class="px-4 py-3 text-right text-gray-600">{{ number_format($player->wins) }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-primary-600">{{ number_format($player->xp) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">Belum ada pemain.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
