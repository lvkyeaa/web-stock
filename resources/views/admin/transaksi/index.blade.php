@extends('layouts.admin')
@section('title', 'Persetujuan Pengajuan Persediaan')

@section('content')
    @include('pengajuan.partials.list-script')

    {{-- Daftar dimuat lewat JSON (pengajuan.data, lingkup semua): ganti halaman / filter / setujui / tolak tidak me-reload halaman --}}
    <div class="space-y-6" x-data="pengajuanList(@js($initial))" @pengajuan-diperbarui.window="load()">
        <div class="rounded-2xl border border-slate-200/70 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-400">Kelola</p>
                    <h2 class="mt-1 text-xl font-semibold tracking-tight text-slate-900">Persetujuan Pengajuan Persediaan</h2>
                </div>
                <div class="flex items-center gap-2 rounded-xl bg-slate-50 px-3 py-2">
                    <span class="text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-400">Total</span>
                    <span class="text-sm font-semibold text-slate-700" x-text="meta.total"></span>
                </div>
            </div>
        </div>

        @include('pengajuan.partials.filter', ['placeholder' => 'Cari kode, persediaan, atau pemohon...'])

        <div class="overflow-hidden rounded-2xl border border-slate-200/70 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/60">
                            <th class="w-14 px-4 py-3 text-left text-[10px] font-semibold uppercase tracking-[0.22em] text-bps-blue-dark">No</th>
                            <th class="w-44 px-4 py-3 text-left text-[10px] font-semibold uppercase tracking-[0.22em] text-bps-blue-dark">Pemohon</th>
                            <th class="px-4 py-3 text-left text-[10px] font-semibold uppercase tracking-[0.22em] text-bps-blue-dark">Daftar Persediaan Diminta</th>
                            <th class="w-28 px-4 py-3 text-left text-[10px] font-semibold uppercase tracking-[0.22em] text-bps-blue-dark">Status</th>
                            <th class="w-36 px-4 py-3 text-left text-[10px] font-semibold uppercase tracking-[0.22em] text-bps-blue-dark">Tanggal</th>
                            <th class="w-44 px-4 py-3 text-center text-[10px] font-semibold uppercase tracking-[0.22em] text-bps-blue-dark">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 transition-opacity" :class="loading && items.length && 'opacity-50'">
                        <template x-for="(item, i) in items" :key="item.id">
                            <tr class="align-top transition hover:bg-slate-50/70">
                                <td class="px-4 py-4 text-sm text-slate-500" x-text="meta.from + i"></td>
                                <td class="px-4 py-4 text-sm font-semibold text-slate-900">
                                    <span x-text="item.pemohon"></span>
                                    <span class="block text-[10px] font-normal text-slate-400" x-text="item.code"></span>
                                </td>

                                {{-- Daftar rincian persediaan dalam 1 pengajuan --}}
                                <td class="px-4 py-4 text-sm text-slate-700">
                                    <div class="space-y-1.5">
                                        <template x-for="(detail, d) in item.items" :key="d">
                                            <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-100 bg-slate-50/60 px-3 py-2">
                                                <div class="min-w-0 flex-1 flex items-center gap-2">
                                                    <span class="font-bold text-bps-blue-dark text-xs bg-slate-200/60 px-2 py-0.5 rounded-md" x-text="'x' + detail.jumlah"></span>
                                                    <span class="truncate text-xs font-semibold text-slate-800" :title="detail.nama_barang" x-text="detail.nama_barang"></span>
                                                </div>
                                                <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-400" x-text="detail.satuan"></span>
                                            </div>
                                        </template>
                                    </div>
                                </td>

                                <td class="px-4 py-4">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold" :class="warnaStatus[item.status]" x-text="item.status_label"></span>
                                </td>

                                <td class="px-4 py-4 text-xs whitespace-nowrap text-slate-500" x-text="item.dibuat"></td>

                                {{-- Aksi: setujui / tolak (pending), keterangan (sudah diproses), cetak PDF (disetujui) --}}
                                <td class="px-4 py-4 text-center">
                                    <div class="flex flex-col gap-2">
                                        <template x-if="item.status_url">
                                            <div class="flex flex-col gap-2">
                                                <button type="button" @click="openSetujuiModal(item)"
                                                    class="w-full rounded-xl bg-gradient-to-r from-bps-blue to-bps-blue-dark hover:from-bps-blue-dark hover:to-bps-blue px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition cursor-pointer">
                                                    Setujui
                                                </button>
                                                <button type="button" @click="openTolakModal(item)"
                                                    class="w-full rounded-xl bg-gradient-to-r from-rose-500 to-rose-600 hover:from-rose-600 hover:to-rose-500 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition cursor-pointer">
                                                    Tolak
                                                </button>
                                            </div>
                                        </template>
                                        <template x-if="!item.status_url">
                                            <div class="text-left mb-1">
                                                <span class="block text-[11px] font-semibold text-slate-500"
                                                    x-text="item.status === 'dibatalkan' ? 'Dibatalkan pemohon' : 'Selesai'"></span>
                                                <span x-show="item.alasan" class="block text-[10px] italic text-rose-500 max-w-[130px] truncate"
                                                    :title="item.alasan" x-text="'Alasan: ' + item.alasan"></span>
                                            </div>
                                        </template>

                                        <a x-show="item.pdf_url" :href="item.pdf_url" target="_blank"
                                            class="w-full flex items-center justify-center gap-1 rounded-xl border border-red-200 bg-red-50/70 hover:bg-red-100 px-2.5 py-1.5 text-xs font-bold text-red-700 transition shadow-xs">
                                            <svg class="w-3.5 h-3.5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                            </svg>
                                            Cetak PDF
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <tr x-show="loading && !items.length">
                            <td colspan="6" class="px-6 py-12 text-center text-gray-400 text-sm">Memuat data pengajuan...</td>
                        </tr>
                        <tr x-show="!loading && !error && !items.length" x-cloak>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-400 text-sm">Belum ada data pengajuan.</td>
                        </tr>
                        <tr x-show="error" x-cloak>
                            <td colspan="6" class="px-6 py-12 text-center text-sm text-red-600">
                                <span x-text="error"></span>
                                <button type="button" @click="load()" class="ml-2 font-semibold underline cursor-pointer">Coba lagi</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-gray-100" x-show="meta.total > 0" x-cloak>
                @include('barang.partials.pagination')
            </div>
        </div>
    </div>

    {{-- Modal Setujui Pengajuan --}}
    <div id="modalSetujui" data-dialog class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 backdrop-blur-sm"
        role="dialog" aria-modal="true" aria-labelledby="judulSetujui">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
            <div class="flex items-start gap-3 mb-4">
                <div class="w-10 h-10 shrink-0 rounded-xl bg-bps-blue/10 text-bps-blue flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <h3 id="judulSetujui" class="text-lg font-bold text-slate-900">Setujui Pengajuan Persediaan?</h3>
                    <p class="text-xs text-slate-500 mt-1">
                        Pengajuan <span id="setujuiKode" class="font-mono font-semibold text-slate-700"></span>
                        dari <span id="setujuiPemohon" class="font-semibold text-slate-700"></span>
                        (<span id="setujuiJumlah"></span> item) akan disetujui dan stok langsung dipotong.
                    </p>
                </div>
                <button type="button" onclick="tutupDialog('modalSetujui')" aria-label="Tutup" title="Tutup"
                    class="ml-auto shrink-0 -mt-1 -mr-1 p-1 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form id="formSetujui" onsubmit="kirimStatus(event, 'disetujui')" class="space-y-4">
                <p data-galat role="alert" class="hidden p-3 rounded-xl bg-red-50 border border-red-200 text-xs text-red-700"></p>
                <div class="flex gap-3">
                    <button type="button" onclick="tutupDialog('modalSetujui')"
                        class="flex-1 px-4 py-2.5 rounded-xl border border-gray-200 text-xs font-semibold text-gray-600 hover:bg-gray-50 cursor-pointer">Batal</button>
                    <button type="submit"
                        class="flex-1 px-4 py-2.5 rounded-xl bg-gradient-to-r from-bps-blue to-bps-blue-dark text-white text-xs font-semibold hover:from-bps-blue-dark hover:to-bps-blue transition cursor-pointer disabled:opacity-60 disabled:cursor-wait">Ya, Setujui</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Tolak Pengajuan --}}
    <div id="modalTolak" data-dialog class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 backdrop-blur-sm"
        role="dialog" aria-modal="true" aria-labelledby="judulTolak">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
            <div class="flex items-start gap-3 mb-4">
                <div class="min-w-0">
                    <h3 id="judulTolak" class="text-lg font-bold text-slate-900 mb-1">Tolak Pengajuan Persediaan</h3>
                    <p class="text-xs text-slate-400">Pengajuan <span id="tolakKode" class="font-mono font-semibold text-slate-600"></span>. Silakan masukkan alasan penolakan permintaan persediaan ini.</p>
                </div>
                <button type="button" onclick="tutupDialog('modalTolak')" aria-label="Tutup" title="Tutup"
                    class="ml-auto shrink-0 -mt-1 -mr-1 p-1 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form id="formTolak" onsubmit="kirimStatus(event, 'ditolak')" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Alasan Penolakan</label>
                    <textarea name="alasan" rows="3" placeholder="Tuliskan alasan penolakan..." maxlength="500" required
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-rose-400 resize-none"></textarea>
                </div>
                <p data-galat role="alert" class="hidden p-3 rounded-xl bg-red-50 border border-red-200 text-xs text-red-700"></p>
                <div class="flex gap-3">
                    <button type="button" onclick="tutupDialog('modalTolak')"
                        class="flex-1 px-4 py-2.5 rounded-xl border border-gray-200 text-xs font-semibold text-gray-600 hover:bg-gray-50 cursor-pointer">Batal</button>
                    <button type="submit" class="flex-1 px-4 py-2.5 rounded-xl bg-rose-500 text-white text-xs font-semibold hover:bg-rose-600 transition cursor-pointer disabled:opacity-60 disabled:cursor-wait">Tolak Pengajuan</button>
                </div>
            </form>
        </div>
    </div>

    <div id="pesanPengajuan" aria-live="polite" class="fixed bottom-4 right-4 left-4 sm:left-auto sm:w-96 z-[60] space-y-2"></div>

    <script>
        const CSRF_TOKEN = @js(csrf_token());
        let pengajuanDipilih = null; // baris pengajuan yang sedang disetujui / ditolak

        function bukaDialog(id, item) {
            pengajuanDipilih = item;
            const dialog = document.getElementById(id);
            dialog.querySelector('form').reset();
            dialog.querySelector('[data-galat]').classList.add('hidden');
            dialog.querySelector('button[type="submit"]').disabled = false;
            dialog.classList.remove('hidden');
        }

        function tutupDialog(id) {
            document.getElementById(id).classList.add('hidden');
        }

        function openSetujuiModal(item) {
            document.getElementById('setujuiKode').textContent = item.code;
            document.getElementById('setujuiPemohon').textContent = item.pemohon;
            document.getElementById('setujuiJumlah').textContent = item.items.length;
            bukaDialog('modalSetujui', item);
            document.querySelector('#modalSetujui button[type="submit"]').focus();
        }

        function openTolakModal(item) {
            document.getElementById('tolakKode').textContent = item.code;
            bukaDialog('modalTolak', item);
            document.querySelector('#modalTolak textarea').focus();
        }

        // Setujui / tolak lewat fetch (JSON); berhasil → dialog ditutup & daftar dimuat ulang, gagal → pesan di dialog
        async function kirimStatus(event, status) {
            event.preventDefault();
            const form = event.target;
            const dialogId = form.closest('[data-dialog]').id;
            const tombol = form.querySelector('button[type="submit"]');
            const galat = form.querySelector('[data-galat]');
            const body = { status };
            if (status === 'ditolak') body.alasan = form.alasan.value;

            tombol.disabled = true;
            galat.classList.add('hidden');

            try {
                const response = await fetch(pengajuanDipilih.status_url, {
                    method: 'PATCH',
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
                    body: JSON.stringify(body),
                });
                const json = await response.json().catch(() => ({}));

                if (!response.ok) {
                    galat.textContent = json.errors ? Object.values(json.errors).flat().join(' ') : (json.message ?? 'Gagal memperbarui pengajuan. Silakan coba lagi.');
                    galat.classList.remove('hidden');
                    muatUlangPengajuan(); // mis. sudah diproses admin lain / dibatalkan pemohon: status di daftar ikut diperbarui
                    return;
                }

                tutupDialog(dialogId);
                tampilkanPesanPengajuan(json.message);
                muatUlangPengajuan();
            } catch (e) {
                galat.textContent = 'Gagal memperbarui pengajuan. Periksa koneksi lalu coba lagi.';
                galat.classList.remove('hidden');
            } finally {
                tombol.disabled = false;
            }
        }
    </script>
@endsection
