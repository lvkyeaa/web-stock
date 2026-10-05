@extends(auth()->user()->hasRole('admin') ? 'layouts.admin' : 'layouts.customer')
@section('title', 'Pengajuan Saya')

@section('content')
@include('pengajuan.partials.list-script')

{{-- Daftar dimuat lewat JSON (pengajuan.data, lingkup saya): ganti halaman / filter / batalkan tidak me-reload halaman --}}
<div class="space-y-6" x-data="pengajuanList(@js($initial))" @pengajuan-diperbarui.window="load()">
    <div>
        <h2 class="text-xl font-semibold tracking-tight text-gray-900">Pengajuan Saya</h2>
        <p class="text-sm text-gray-500 mt-1">Pantau status pengajuan persediaan yang sudah dikirim.</p>
    </div>

    @include('pengajuan.partials.filter', ['placeholder' => 'Cari kode pengajuan atau persediaan...'])

    <div class="space-y-4 transition-opacity" :class="loading && items.length && 'opacity-50'">
        {{-- Satu kartu per pengajuan (tanpa tabel, agar nyaman di layar ponsel) --}}
        <template x-for="item in items" :key="item.id">
            <article class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <header class="flex flex-wrap items-start justify-between gap-x-4 gap-y-2 px-4 sm:px-6 py-4 border-b border-gray-100">
                    <div class="min-w-0">
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">Kode Pengajuan</p>
                        <div class="mt-0.5 flex items-center gap-2" x-data="{ tersalin: false }">
                            <p class="font-mono text-lg sm:text-2xl font-bold tracking-tight text-gray-900 break-all" x-text="item.code"></p>
                            <button type="button" title="Salin kode" :aria-label="`Salin kode ${item.code}`"
                                @click="salinKode(item.code).then(ok => { tersalin = ok; setTimeout(() => tersalin = false, 1500) })"
                                class="shrink-0 inline-flex items-center gap-1 rounded-lg p-1.5 text-gray-400 hover:text-bps-blue hover:bg-bps-blue/10 transition cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-bps-blue/40">
                                <svg x-show="!tersalin" class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                                <svg x-show="tersalin" x-cloak class="w-4 h-4 sm:w-5 sm:h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span x-show="tersalin" x-cloak role="status" class="text-xs font-semibold text-emerald-600">Tersalin</span>
                            </button>
                        </div>
                        <p class="mt-1 text-xs text-gray-500" x-text="`${item.dibuat} · ${item.items.length} item`"></p>
                    </div>
                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-bold" :class="warnaStatus[item.status]" x-text="item.status_label"></span>
                </header>

                <ul class="divide-y divide-gray-50 px-4 sm:px-6">
                    <template x-for="(detail, d) in item.items" :key="d">
                        <li class="flex items-center justify-between gap-3 py-2.5 text-sm">
                            <span class="min-w-0">
                                <span class="font-bold text-gray-900" x-text="'x' + detail.jumlah"></span>
                                <span class="text-gray-700" x-text="detail.nama_barang"></span>
                            </span>
                            <span class="shrink-0 text-[11px] text-gray-400" x-text="detail.satuan"></span>
                        </li>
                    </template>
                </ul>

                <footer class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-4 sm:px-6 py-3 bg-gray-50/70 border-t border-gray-100">
                    <p class="text-xs text-gray-500 min-w-0">
                        <span class="font-semibold text-gray-600">Catatan admin:</span>
                        <span class="italic" x-text="item.alasan || '-'"></span>
                    </p>

                    {{-- Cetak PDF (disetujui), batalkan (pending milik sendiri), atau keterangan --}}
                    <a x-show="item.pdf_url" :href="item.pdf_url" target="_blank"
                        class="inline-flex shrink-0 items-center justify-center gap-1.5 px-3 py-2 rounded-xl border border-red-200 bg-red-50 text-red-700 hover:bg-red-100 text-xs font-bold transition">
                        <svg class="w-3.5 h-3.5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        Cetak PDF
                    </a>
                    <button type="button" x-show="item.batal_url" @click="bukaBatal(item)"
                        class="inline-flex shrink-0 items-center justify-center gap-1.5 px-3 py-2 rounded-xl border border-rose-200 bg-white text-rose-600 hover:bg-rose-50 text-xs font-bold transition cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Batalkan Pengajuan
                    </button>
                    <span x-show="!item.pdf_url && !item.batal_url" class="text-xs text-gray-400 italic"
                        x-text="item.status === 'dibatalkan' ? 'Dibatalkan' : 'Belum Di-ACC'"></span>
                </footer>
            </article>
        </template>

        <div x-show="loading && !items.length" class="text-center py-16 text-sm text-gray-400">Memuat data pengajuan...</div>
        <div x-show="!loading && !error && !items.length" x-cloak class="text-center py-16 bg-white border border-gray-100 rounded-2xl shadow-sm space-y-4">
            <h4 class="text-base font-bold text-gray-500"
                x-text="filters.q || filters.status ? 'Tidak ada pengajuan yang cocok dengan filter' : 'Belum ada riwayat pengajuan'"></h4>
        </div>
        <div x-show="error" x-cloak class="text-center py-12 text-sm text-red-600">
            <span x-text="error"></span>
            <button type="button" @click="load()" class="ml-2 font-semibold underline cursor-pointer">Coba lagi</button>
        </div>
    </div>

    @include('barang.partials.pagination')
</div>

{{-- Dialog konfirmasi batalkan pengajuan --}}
<div id="modalBatal" data-dialog class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
    role="dialog" aria-modal="true" aria-labelledby="judulBatal">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
        <div class="flex items-start gap-3 mb-4">
            <div class="w-10 h-10 shrink-0 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z" />
                </svg>
            </div>
            <div class="min-w-0">
                <h3 id="judulBatal" class="text-lg font-bold text-gray-900">Batalkan Pengajuan?</h3>
                <p class="text-xs text-gray-500 mt-1">
                    Pengajuan <span id="batalKode" class="font-mono font-semibold text-gray-700"></span> akan dibatalkan dan tidak bisa diproses admin lagi.
                    Stok yang dipesan untuk pengajuan ini dilepas.
                </p>
            </div>
            <button type="button" onclick="tutupBatal()" aria-label="Tutup" title="Tutup"
                class="ml-auto shrink-0 -mt-1 -mr-1 p-1 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <p id="batalGalat" role="alert" class="hidden mb-4 p-3 rounded-xl bg-red-50 border border-red-200 text-xs text-red-700"></p>

        <div class="flex gap-3">
            <button type="button" onclick="tutupBatal()"
                class="flex-1 px-4 py-2.5 rounded-xl border border-gray-200 text-sm font-semibold text-gray-600 hover:bg-gray-50 cursor-pointer">Kembali</button>
            <button type="button" id="tombolBatal" onclick="kirimBatal()"
                class="flex-1 px-4 py-2.5 rounded-xl bg-gradient-to-r from-rose-500 to-rose-600 text-white text-sm font-semibold transition cursor-pointer disabled:opacity-60 disabled:cursor-wait">Ya, Batalkan</button>
        </div>
    </div>
</div>

<div id="pesanPengajuan" aria-live="polite" class="fixed bottom-4 right-4 left-4 sm:left-auto sm:w-96 z-[60] space-y-2"></div>

<script>
    // ─── Batalkan pengajuan (fetch, tanpa reload) ───
    let pengajuanDibatalkan = null; // baris pengajuan yang sedang dibatalkan

    function bukaBatal(item) {
        pengajuanDibatalkan = item;
        document.getElementById('batalKode').textContent = item.code;
        document.getElementById('batalGalat').classList.add('hidden');
        document.getElementById('tombolBatal').disabled = false;
        document.getElementById('modalBatal').classList.remove('hidden');
    }

    function tutupBatal() {
        document.getElementById('modalBatal').classList.add('hidden');
    }

    async function kirimBatal() {
        const tombol = document.getElementById('tombolBatal');
        const galat = document.getElementById('batalGalat');
        tombol.disabled = true;
        galat.classList.add('hidden');

        try {
            const response = await fetch(pengajuanDibatalkan.batal_url, {
                method: 'PATCH',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': @js(csrf_token()) },
            });
            const json = await response.json().catch(() => ({}));

            // Berhasil maupun gagal (mis. sudah diproses admin), daftar dimuat ulang agar statusnya sesuai
            muatUlangPengajuan();

            if (!response.ok) {
                galat.textContent = json.message ?? 'Gagal membatalkan pengajuan. Silakan coba lagi.';
                galat.classList.remove('hidden');
                return;
            }

            tutupBatal();
            tampilkanPesanPengajuan(json.message);
        } catch (e) {
            galat.textContent = 'Gagal membatalkan pengajuan. Periksa koneksi lalu coba lagi.';
            galat.classList.remove('hidden');
        } finally {
            tombol.disabled = false;
        }
    }

    // Salin kode pengajuan. Clipboard API hanya ada di HTTPS/localhost; di HTTP (mis. jaringan kantor) pakai cara lama.
    async function salinKode(teks) {
        try {
            await navigator.clipboard.writeText(teks);
            return true;
        } catch (e) {
            const el = document.createElement('textarea');
            el.value = teks;
            el.setAttribute('readonly', '');
            el.style.position = 'fixed';
            el.style.opacity = '0';
            document.body.appendChild(el);
            el.select();
            const ok = document.execCommand('copy');
            el.remove();
            return ok;
        }
    }
</script>
@endsection
