<div class="mx-auto max-w-lg px-4 py-10">
    <div class="rounded-2xl bg-white p-8 text-center shadow-xs">
        <div class="mx-auto flex size-16 items-center justify-center rounded-2xl bg-linear-to-br from-primary-500 to-secondary-600 text-3xl">
            ⚔️
        </div>
        <h2 class="mt-4 text-xl font-bold text-gray-900">Lawan Pemain Lain</h2>
        <p class="mt-2 text-sm text-gray-600">
            Kami akan mencarikan lawan dengan level yang mirip. Jika belum ada,
            kamu akan menunggu sampai ada yang bergabung.
        </p>

        <button
            wire:click="findMatch"
            wire:loading.attr="disabled"
            wire:target="findMatch"
            class="mt-6 inline-flex w-full items-center justify-center gap-2 rounded-lg bg-primary-600 px-5 py-3 font-semibold text-white transition hover:bg-primary-700 disabled:opacity-60">
            <span wire:loading.remove wire:target="findMatch">Cari Lawan</span>
            <span wire:loading wire:target="findMatch">Mencari…</span>
        </button>
    </div>
</div>
