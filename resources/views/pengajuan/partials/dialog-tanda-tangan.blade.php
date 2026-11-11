{{-- Dialog tanda tangan pemohon & ketua tim (state ttd dari pengajuanList: baris pengajuan); dibuka lewat lihatTtd(item) --}}
<div x-show="ttd" x-cloak class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
    role="dialog" aria-modal="true" aria-labelledby="judulTtd" @click.self="ttd = null" @keydown.escape.window="ttd = null">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
        <div class="flex items-start justify-between gap-3 mb-4">
            <div class="min-w-0">
                <h3 id="judulTtd" class="text-lg font-bold text-gray-900">Tanda Tangan</h3>
                <p class="text-xs text-gray-500 mt-1 font-mono" x-text="ttd?.code"></p>
            </div>
            <button type="button" @click="ttd = null" aria-label="Tutup" title="Tutup"
                class="shrink-0 -mt-1 -mr-1 p-1 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <figure>
                <figcaption class="mb-1.5 text-xs font-bold text-gray-500 uppercase tracking-wider">Pemohon</figcaption>
                <img :src="ttd?.ttd_pemohon" alt="Tanda tangan pemohon" class="w-full h-auto rounded-xl border border-gray-100 bg-white">
                <p class="mt-1 text-xs text-gray-600" x-text="ttd?.pemohon"></p>
            </figure>
            <figure>
                <figcaption class="mb-1.5 text-xs font-bold text-gray-500 uppercase tracking-wider">Ketua Tim</figcaption>
                <img x-show="ttd?.ttd_ketua" :src="ttd?.ttd_ketua" alt="Tanda tangan ketua tim" class="w-full h-auto rounded-xl border border-gray-100 bg-white">
                <p x-show="!ttd?.ttd_ketua" class="flex items-center justify-center aspect-[2/1] rounded-xl border border-dashed border-gray-200 text-xs text-gray-400">Belum ditandatangani</p>
                <p class="mt-1 text-xs text-gray-600" x-text="ttd?.ketua"></p>
            </figure>
        </div>
    </div>
</div>
