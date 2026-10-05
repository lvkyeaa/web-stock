{{-- Navigasi halaman daftar barang (state dari barangList) --}}
<div x-show="meta.total > 0" x-cloak class="flex flex-col sm:flex-row items-center justify-between gap-3">
    <p class="text-xs text-slate-500">
        Menampilkan <span class="font-semibold text-slate-700" x-text="meta.from"></span>–<span class="font-semibold text-slate-700" x-text="meta.to"></span>
        dari <span class="font-semibold text-slate-700" x-text="meta.total"></span> item
    </p>

    <nav x-show="meta.last_page > 1" class="flex flex-wrap items-center justify-center gap-1" aria-label="Halaman">
        <button type="button" @click="goTo(meta.current_page - 1)" :disabled="meta.current_page <= 1" aria-label="Sebelumnya"
            class="h-8 px-2.5 rounded-lg border border-slate-200 bg-white text-xs font-semibold text-slate-600 hover:bg-slate-50 transition cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed">
            ‹
        </button>
        <template x-for="(p, i) in pages" :key="i">
            <button type="button" @click="goTo(p)" :disabled="p === '…'" x-text="p"
                :aria-current="p === meta.current_page ? 'page' : null"
                :class="p === meta.current_page ? 'bg-bps-blue border-bps-blue text-white' : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50'"
                class="min-w-8 h-8 px-2 rounded-lg border text-xs font-semibold transition cursor-pointer disabled:cursor-default disabled:hover:bg-white"></button>
        </template>
        <button type="button" @click="goTo(meta.current_page + 1)" :disabled="meta.current_page >= meta.last_page" aria-label="Berikutnya"
            class="h-8 px-2.5 rounded-lg border border-slate-200 bg-white text-xs font-semibold text-slate-600 hover:bg-slate-50 transition cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed">
            ›
        </button>
    </nav>
</div>
