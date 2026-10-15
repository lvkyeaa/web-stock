{{-- Modal Asisten AI. Buka dengan $dispatch('buka-asisten-ai').
     $konteks, $judul, $keterangan, $placeholder, $contoh: lihat partials.asisten-ai.kotak --}}
<div x-data="{ buka: false }" @buka-asisten-ai.window="buka = true; $nextTick(() => $el.querySelector('textarea').focus())"
    @keydown.escape.window="buka = false"
    @click.self="buka = false" x-show="buka" x-cloak x-transition.opacity
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-xs">
    <div role="dialog" aria-modal="true" aria-labelledby="judulAsistenAi"
        class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl">
        <div class="mb-5 flex items-start justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-bps-orange to-bps-blue text-white shadow-md">
                    @include('partials.asisten-ai.ikon', ['class' => 'h-5 w-5'])
                </div>
                <div>
                    <h3 id="judulAsistenAi" class="text-lg font-bold text-gray-900">{{ $judul }}</h3>
                    <p class="text-xs text-slate-500">{{ $keterangan }}</p>
                </div>
            </div>
            <button type="button" @click="buka = false" aria-label="Tutup" class="text-gray-400 hover:text-gray-600 cursor-pointer">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        @include('partials.asisten-ai.kotak')
    </div>
</div>
