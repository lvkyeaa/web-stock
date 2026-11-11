{{-- Tombol "Pasang Aplikasi": hanya tampil jika browser menawarkan pemasangan (belum terpasang, browser mendukung) --}}
<button type="button" x-data="{ bisa: !!window.tawaranPasang }" x-show="bisa" x-cloak
    @pasang-tersedia.window="bisa = !!window.tawaranPasang"
    @click="window.tawaranPasang.prompt(); window.tawaranPasang.userChoice.then(() => { window.tawaranPasang = null; bisa = false })"
    title="Pasang Aplikasi" aria-label="Pasang Aplikasi"
    class="inline-flex items-center gap-1.5 p-2 sm:px-3 sm:py-1.5 rounded-xl border border-bps-blue/20 bg-bps-blue/5 text-bps-blue hover:bg-bps-blue/10 text-xs font-semibold transition cursor-pointer">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
    </svg>
    <span class="hidden sm:inline">Pasang Aplikasi</span>
</button>
