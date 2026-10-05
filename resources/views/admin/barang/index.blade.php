@extends('layouts.admin')
@section('title', 'Persediaan & Stok')

@section('content')
    @include('barang.partials.list-script')

    {{-- Daftar dimuat lewat JSON (barang.data): ganti halaman / filter / tambah / edit / ubah stok / hapus tidak me-reload halaman --}}
    <div class="space-y-6" x-data="barangList(@js($initial))" @persediaan-diperbarui.window="load()">
        {{-- 1. JUDUL + TAMBAH MANUAL --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold tracking-tight text-slate-800">Persediaan &amp; Stok</h2>
                <p class="text-xs text-slate-400 mt-0.5">Manajemen stok logistik dan persediaan kantor</p>
            </div>

            <button type="button" onclick="bersihkanGalat(document.querySelector('#modalTambah form')); document.getElementById('modalTambah').classList.remove('hidden')"
                class="flex items-center justify-center gap-2 bg-gradient-to-r from-bps-blue to-bps-blue-dark hover:from-bps-blue-dark hover:to-bps-blue text-white px-4 py-2.5 rounded-xl text-xs font-bold transition shadow-sm hover:shadow-md cursor-pointer whitespace-nowrap uppercase tracking-wider">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Tambah Manual
            </button>
        </div>

        {{-- 2. IMPOR EXCEL: diproses di latar belakang (queue), status dilihat di Riwayat Impor --}}
        <div class="rounded-2xl border border-orange-100 bg-white p-4 shadow-sm flex flex-col lg:flex-row lg:items-center gap-3">
            <div class="flex items-start gap-3 min-w-0 lg:flex-1">
                <div class="w-10 h-10 shrink-0 rounded-xl bg-orange-50 text-bps-orange flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-slate-800">Impor dari Excel</p>
                    <p class="text-xs text-slate-400">Kolom: <span class="font-mono">nama_barang</span>, <span class="font-mono">jumlah</span>, <span class="font-mono">satuan</span>. Nama yang sudah ada stoknya ditambah, nama baru dibuat.</p>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center gap-2 w-full lg:w-auto">
                <form action="{{ route('barang.import') }}" method="POST" enctype="multipart/form-data" onsubmit="kirimImport(event)"
                    class="flex items-center gap-2 p-1.5 pl-3 rounded-xl border border-slate-200 bg-slate-50 w-full sm:w-auto sm:min-w-[22rem]">
                    @csrf
                    <input type="file" name="file_excel" required accept=".xlsx,.xls,.csv"
                        class="block min-w-0 flex-1 text-[11px] text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:font-bold file:bg-orange-50 file:text-bps-orange hover:file:bg-orange-100 cursor-pointer focus:outline-none" />
                    <button type="submit"
                        class="shrink-0 bg-gradient-to-r from-bps-blue to-bps-blue-dark hover:from-bps-blue-dark hover:to-bps-blue text-white text-[11px] font-bold py-2 px-3 rounded-lg shadow-sm hover:shadow transition-all cursor-pointer whitespace-nowrap uppercase tracking-wider disabled:opacity-60 disabled:cursor-wait">
                        Impor
                    </button>
                </form>

                <button type="button" onclick="window.dispatchEvent(new CustomEvent('buka-riwayat-impor'))"
                    class="flex items-center justify-center gap-2 bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 px-4 py-2.5 rounded-xl text-xs font-bold transition shadow-sm cursor-pointer whitespace-nowrap uppercase tracking-wider">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Riwayat Impor
                </button>
            </div>
        </div>

        {{-- 3. FILTER: cari (debounce), urutan, stok habis — daftar dimuat ulang lewat JSON --}}
        <form action="{{ route('barang.index') }}" method="GET" @submit.prevent="search()"
            class="rounded-2xl border border-slate-200/70 bg-white p-3 shadow-sm flex flex-col md:flex-row md:items-center gap-3">
            <div class="flex items-center gap-2 md:flex-1 min-w-0">
                <div class="relative flex-1 min-w-0">
                    <input
                        type="text"
                        name="search"
                        x-model="filters.search" @input.debounce.400ms="search()"
                        placeholder="Cari nama persediaan..."
                        class="w-full pl-9 pr-9 py-2 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-bps-blue">

                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>

                    {{-- Hapus kata kunci (di dalam kolom pencarian) --}}
                    <button type="button" x-show="filters.search" x-cloak @click="resetSearch()" title="Hapus pencarian" aria-label="Hapus pencarian"
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <button type="submit" class="shrink-0 bg-gradient-to-r from-bps-blue to-bps-blue-dark text-white px-4 py-2 rounded-xl text-xs font-bold transition shadow-sm hover:shadow cursor-pointer uppercase tracking-wider">
                    Cari
                </button>
            </div>

            <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                {{-- Urutan: terpopuler = paling sering diminta (jumlah pengajuan disetujui) --}}
                <label class="flex items-center gap-2 text-[11px] font-semibold text-slate-500 whitespace-nowrap">
                    Urutkan
                    <select x-model="filters.urut" @change="search()"
                        class="py-2 pl-3 pr-8 rounded-xl border border-slate-200 bg-white text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-bps-blue cursor-pointer">
                        <option value="populer">Terpopuler (90 hari terakhir)</option>
                        <option value="populer_total">Terpopuler (total)</option>
                        <option value="terbaru">Terbaru</option>
                        <option value="nama">Nama A–Z</option>
                    </select>
                </label>

                <label class="flex items-center gap-2 text-[11px] font-semibold text-slate-500 cursor-pointer select-none whitespace-nowrap">
                    <input type="checkbox" role="switch" name="stok_habis" value="1" x-model="filters.stokHabis" @change="search()" class="peer sr-only">
                    <span aria-hidden="true" class="relative inline-block w-9 h-5 shrink-0 rounded-full bg-slate-300 transition-colors peer-checked:bg-bps-blue peer-focus-visible:ring-2 peer-focus-visible:ring-bps-blue/40 after:absolute after:top-0.5 after:left-0.5 after:w-4 after:h-4 after:rounded-full after:bg-white after:shadow-sm after:transition-transform peer-checked:after:translate-x-4"></span>
                    Tampilkan stok habis
                </label>
            </div>
        </form>

        {{-- TABEL DAFTAR BARANG --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-bps-blue/5 border-b border-gray-100">
                            <th class="text-left px-6 py-4 text-xs font-bold text-bps-blue uppercase tracking-wider">No</th>
                            <th class="text-left px-6 py-4 text-xs font-bold text-bps-blue uppercase tracking-wider">Foto</th>
                            <th class="text-left px-6 py-4 text-xs font-bold text-bps-blue uppercase tracking-wider">Kode</th>
                            <th class="text-left px-6 py-4 text-xs font-bold text-bps-blue uppercase tracking-wider">Nama Persediaan</th>
                            <th class="text-left px-6 py-4 text-xs font-bold text-bps-blue uppercase tracking-wider">Stock</th>
                            <th class="text-left px-6 py-4 text-xs font-bold text-bps-blue uppercase tracking-wider">Diminta</th>
                            <th class="text-left px-6 py-4 text-xs font-bold text-bps-blue uppercase tracking-wider">Satuan</th>
                            <th class="text-left px-6 py-4 text-xs font-bold text-bps-blue uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 transition-opacity" :class="loading && items.length && 'opacity-50'">
                        <template x-for="(item, i) in items" :key="item.id">
                            {{-- Stok habis: baris abu-abu, kolom aksi tetap aktif agar admin bisa menambah stok --}}
                            <tr class="transition" :class="item.tersedia > 0 ? 'hover:bg-gray-50' : 'bg-slate-50 [&>td:not(:last-child)]:opacity-50 [&_img]:grayscale'">
                                <td class="px-6 py-4 text-sm text-gray-500" x-text="meta.from + i"></td>

                                {{-- FOTO BARANG --}}
                                <td class="px-6 py-4">
                                    <div class="w-12 h-12 rounded-xl bg-slate-100 overflow-hidden border border-slate-200 flex-shrink-0 flex items-center justify-center">
                                        <img :src="item.foto_url" :alt="item.nama_barang" class="w-full h-full object-cover">
                                    </div>
                                </td>

                                <td class="px-6 py-4 text-sm text-gray-500 font-mono" x-text="item.stock_id ?? '-'"></td>
                                <td class="px-6 py-4 text-sm font-semibold text-gray-900" x-text="item.nama_barang"></td>
                                {{-- Stok fisik; jika ada pengajuan pending, tampilkan yang sudah diajukan & sisa tersedia --}}
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-sm font-bold tabular-nums" x-text="item.stock"
                                            :class="item.tersedia <= 0 ? 'text-slate-500' : (item.tersedia < 5 ? 'text-red-600' : 'text-bps-green')"></span>
                                        <span x-show="item.tersedia <= 0" class="px-1.5 py-0.5 rounded-md bg-slate-200 text-[10px] font-bold uppercase tracking-wider text-slate-500">Habis</span>
                                    </div>
                                    <p x-show="item.dipesan > 0" class="mt-0.5 text-[11px] font-medium text-amber-600 whitespace-nowrap"
                                        x-text="`${item.dipesan} diajukan · ${item.tersedia} tersedia`"></p>
                                </td>
                                {{-- Popularitas: jumlah pengajuan yang disetujui (90 hari terakhir & total) --}}
                                {{-- Gaya sama dengan kartu katalog; angka tidak diringkas (tampilan pengelolaan) --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <template x-if="item.diminta_total > 0">
                                        <div>
                                            <p class="flex items-center gap-1 text-xs" :class="item.diminta_90_hari > 0 ? 'text-slate-700' : 'text-slate-400'">
                                                <svg class="w-4 h-4 shrink-0" :class="item.diminta_90_hari > 0 ? 'text-bps-orange' : 'text-slate-300'" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                                    <path fill-rule="evenodd" d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.214.33-.403.713-.57 1.116-.334.804-.614 1.768-.84 2.734a31.365 31.365 0 00-.613 3.58 2.64 2.64 0 01-.945-1.067c-.328-.68-.398-1.534-.398-2.654A1 1 0 005.05 6.05 6.981 6.981 0 003 11a7 7 0 1011.95-4.95c-.592-.591-.98-.985-1.348-1.467-.363-.476-.724-1.063-1.207-2.03zM12.12 15.12A3 3 0 017 13s.879.5 2.5.5c0-1 .5-4 1.25-4.5.5 1 .786 1.293 1.371 1.879A2.99 2.99 0 0113 13a2.99 2.99 0 01-.879 2.121z" clip-rule="evenodd" />
                                                </svg>
                                                <span class="text-sm font-bold tabular-nums" :class="item.diminta_90_hari > 0 ? 'text-slate-800' : 'text-slate-400'" x-text="item.diminta_90_hari + '×'"></span>
                                                <span class="font-medium">diminta</span>
                                                <span class="text-slate-400">· 90 hari terakhir</span>
                                            </p>
                                            <p class="mt-0.5 pl-5 text-[11px] text-slate-400" x-text="`${item.diminta_total}× total`"></p>
                                        </div>
                                    </template>
                                    <span x-show="item.diminta_total === 0" class="text-sm text-slate-300" title="Belum pernah diminta">—</span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600" x-text="item.satuan"></td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <button title="Ubah Stok"
                                            @click="openStockModal(item.id, item.nama_barang, item.stock, item.dipesan, item.satuan)"
                                            class="p-1.5 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-100 transition cursor-pointer">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                                            </svg>
                                        </button>
                                        <button title="Edit Persediaan"
                                            @click="openEditModal(item.id, item.stock_id ?? '', item.nama_barang, item.satuan, item.foto_url)"
                                            class="p-1.5 rounded-lg bg-bps-blue/10 text-bps-blue hover:bg-bps-blue/20 transition cursor-pointer">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        {{-- Persediaan yang pernah diajukan tidak bisa dihapus (dicek juga di server) --}}
                                        <button type="button" @click="hapusPersediaan(item)" :disabled="!item.bisa_dihapus"
                                            :title="item.bisa_dihapus ? 'Hapus Persediaan' : 'Tidak bisa dihapus: sudah pernah diajukan'"
                                            class="p-1.5 rounded-lg bg-red-50 text-red-500 hover:bg-red-100 transition cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-red-50">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <tr x-show="loading && !items.length">
                            <td colspan="8" class="px-6 py-12 text-center text-gray-400 text-sm">Memuat data persediaan...</td>
                        </tr>
                        <tr x-show="!loading && !error && !items.length" x-cloak>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-400 text-sm">Belum ada data persediaan.</td>
                        </tr>
                        <tr x-show="error" x-cloak>
                            <td colspan="8" class="px-6 py-12 text-center text-sm text-red-600">
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

    {{-- MODAL TAMBAH BARANG --}}
    <div id="modalTambah" data-dialog class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-lg font-bold text-gray-900">Tambah Persediaan</h3>
                <button onclick="document.getElementById('modalTambah').classList.add('hidden')"
                    class="text-gray-400 hover:text-gray-600 cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form action="{{ route('barang.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4" onsubmit="kirimFormPersediaan(event, 'modalTambah')">
                @csrf
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Kode Persediaan <span class="font-normal text-gray-400">(opsional)</span></label>
                    <input type="text" name="stock_id" maxlength="50"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-bps-blue">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Nama Persediaan</label>
                    <input type="text" name="nama_barang" required
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-bps-blue">
                </div>

                {{-- Input Foto Barang --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Foto Persediaan (Auto Kompres)</label>
                    <input type="file" name="foto" accept="image/*" onchange="autoCompressImage(this)"
                        class="block w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-bps-blue/10 file:text-bps-blue hover:file:bg-bps-blue/20 cursor-pointer" />
                    <span id="compressStatusTambah" class="text-[11px] text-bps-blue mt-1 block font-medium"></span>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Stock</label>
                        <input type="number" name="stock" min="0" required
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-bps-blue">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Satuan</label>
                        <input type="text" name="satuan" placeholder="pcs, unit, dll" required
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-bps-blue">
                    </div>
                </div>
                <p data-galat role="alert" class="hidden p-3 rounded-xl bg-red-50 border border-red-200 text-xs text-red-700"></p>
                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="document.getElementById('modalTambah').classList.add('hidden')"
                        class="flex-1 px-4 py-2.5 rounded-xl border border-gray-200 text-sm font-semibold text-gray-600 hover:bg-gray-50 cursor-pointer">Batal</button>
                    <button type="submit"
                        class="disabled:opacity-60 disabled:cursor-wait flex-1 px-4 py-2.5 rounded-xl bg-gradient-to-r from-bps-blue to-bps-blue-dark text-white text-sm font-semibold hover:from-bps-blue-dark hover:to-bps-blue transition cursor-pointer">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL EDIT BARANG --}}
    <div id="modalEdit" data-dialog class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-lg font-bold text-gray-900">Edit Persediaan</h3>
                <button onclick="document.getElementById('modalEdit').classList.add('hidden')"
                    class="text-gray-400 hover:text-gray-600 cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form id="formEdit" method="POST" enctype="multipart/form-data" class="space-y-4" onsubmit="kirimFormPersediaan(event, 'modalEdit')">
                @csrf @method('PUT')
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Kode Persediaan</label>
                    <input type="text" id="editKode" name="stock_id" maxlength="50"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-bps-blue">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Nama Persediaan</label>
                    <input type="text" id="editNama" name="nama_barang" required
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-bps-blue">
                </div>

                {{-- Input Pratinjau & Ubah Foto --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Ganti Foto Persediaan (Auto Kompres)</label>
                    <div class="flex items-center gap-3 mb-2">
                        <img id="editPreviewFoto" src="" class="w-12 h-12 rounded-xl object-cover border border-gray-200">
                        <span class="text-xs text-gray-400">Foto saat ini</span>
                    </div>
                    <input type="file" name="foto" accept="image/*" onchange="autoCompressImage(this)"
                        class="block w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-bps-blue/10 file:text-bps-blue hover:file:bg-bps-blue/20 cursor-pointer" />
                    <span id="compressStatusEdit" class="text-[11px] text-bps-blue mt-1 block font-medium"></span>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Satuan</label>
                    <input type="text" id="editSatuan" name="satuan" required
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-bps-blue">
                </div>
                <p data-galat role="alert" class="hidden p-3 rounded-xl bg-red-50 border border-red-200 text-xs text-red-700"></p>
                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="document.getElementById('modalEdit').classList.add('hidden')"
                        class="flex-1 px-4 py-2.5 rounded-xl border border-gray-200 text-sm font-semibold text-gray-600 hover:bg-gray-50 cursor-pointer">Batal</button>
                    <button type="submit"
                        class="disabled:opacity-60 disabled:cursor-wait flex-1 px-4 py-2.5 rounded-xl bg-bps-orange text-white text-sm font-semibold hover:bg-bps-orange-dark transition cursor-pointer">Update</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL UBAH STOK --}}
    <div id="modalStock" data-dialog class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Ubah Stok</h3>
                    <p id="stockInfo" class="text-xs text-gray-400 mt-0.5"></p>
                </div>
                <button onclick="document.getElementById('modalStock').classList.add('hidden')"
                    class="text-gray-400 hover:text-gray-600 cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form id="formStock" method="POST" class="space-y-4" onsubmit="kirimFormPersediaan(event, 'modalStock')">
                @csrf @method('PATCH')
                <div class="grid grid-cols-3 gap-2 text-xs font-semibold">
                    <label class="cursor-pointer">
                        <input type="radio" name="operasi" value="tambah" class="peer sr-only" checked>
                        <span class="block text-center px-3 py-2 rounded-xl border border-gray-200 text-gray-600 peer-checked:border-emerald-500 peer-checked:bg-emerald-50 peer-checked:text-emerald-700">+ Tambah</span>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="operasi" value="kurang" class="peer sr-only">
                        <span class="block text-center px-3 py-2 rounded-xl border border-gray-200 text-gray-600 peer-checked:border-rose-500 peer-checked:bg-rose-50 peer-checked:text-rose-700">− Kurangi</span>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="operasi" value="set" class="peer sr-only">
                        <span class="block text-center px-3 py-2 rounded-xl border border-gray-200 text-gray-600 peer-checked:border-bps-blue peer-checked:bg-bps-blue/10 peer-checked:text-bps-blue">= Atur Jadi</span>
                    </label>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Jumlah</label>
                    <input type="number" name="jumlah" min="0" required
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-bps-blue">
                </div>
                <p data-galat role="alert" class="hidden p-3 rounded-xl bg-red-50 border border-red-200 text-xs text-red-700"></p>
                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="document.getElementById('modalStock').classList.add('hidden')"
                        class="flex-1 px-4 py-2.5 rounded-xl border border-gray-200 text-sm font-semibold text-gray-600 hover:bg-gray-50 cursor-pointer">Batal</button>
                    <button type="submit"
                        class="disabled:opacity-60 disabled:cursor-wait flex-1 px-4 py-2.5 rounded-xl bg-gradient-to-r from-bps-blue to-bps-blue-dark text-white text-sm font-semibold hover:from-bps-blue-dark hover:to-bps-blue transition cursor-pointer">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- DIALOG RIWAYAT IMPOR: daftar unggahan & statusnya, dimuat ulang manual lewat tombol Muat Ulang --}}
    <div x-data="riwayatImport(@js(route('barang.import.riwayat')))" @buka-riwayat-impor.window="buka()" @keydown.escape.window="open = false">
        <div x-show="open" x-cloak class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" @click.self="open = false">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[85vh] flex flex-col" role="dialog" aria-modal="true" aria-labelledby="judulRiwayatImpor">
                <div class="flex items-center justify-between gap-3 px-6 py-4 border-b border-gray-100">
                    <div>
                        <h3 id="judulRiwayatImpor" class="text-lg font-bold text-gray-900">Riwayat Impor</h3>
                        <p class="text-xs text-gray-400 mt-0.5">20 unggahan terakhir. Impor berjalan di latar belakang; tekan Muat Ulang untuk melihat status terbaru.</p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <button type="button" @click="muat()" :disabled="loading"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border border-gray-200 text-xs font-semibold text-gray-600 hover:bg-gray-50 transition cursor-pointer disabled:opacity-60 disabled:cursor-wait">
                            <svg class="w-4 h-4" :class="loading && 'animate-spin'" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Muat Ulang
                        </button>
                        <button type="button" @click="open = false" class="text-gray-400 hover:text-gray-600 cursor-pointer" aria-label="Tutup">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="overflow-y-auto px-6 py-4 space-y-3">
                    <p x-show="error" x-cloak class="p-3 rounded-xl bg-red-50 border border-red-200 text-xs text-red-700" x-text="error"></p>
                    <p x-show="loading && !items.length" class="py-8 text-center text-sm text-gray-400">Memuat riwayat impor...</p>
                    <p x-show="!loading && !error && !items.length" x-cloak class="py-8 text-center text-sm text-gray-400">Belum ada file yang diimpor.</p>

                    <template x-for="item in items" :key="item.id">
                        <div class="rounded-xl border border-gray-100 p-4">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-gray-900 break-all" x-text="item.nama_file"></p>
                                    <p class="text-xs text-gray-400 mt-0.5" x-text="`Diunggah ${item.diunggah}${item.oleh ? ' oleh ' + item.oleh : ''}`"></p>
                                </div>
                                <span class="inline-flex shrink-0 px-2.5 py-1 rounded-full text-xs font-bold" :class="warnaStatus[item.status]" x-text="item.status_label"></span>
                            </div>
                            <p x-show="item.keterangan" class="mt-2 text-xs whitespace-pre-line"
                                :class="item.status === 'error' ? 'text-red-700' : 'text-gray-600'" x-text="item.keterangan"></p>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    {{-- Pesan hasil aksi (pengganti flash message, halaman tidak di-reload) --}}
    <div id="pesanPersediaan" aria-live="polite" class="fixed bottom-4 right-4 left-4 sm:left-auto sm:w-96 z-[60] space-y-2"></div>

    <script>
        const CSRF_TOKEN = @js(csrf_token());

        // Tampilkan pesan singkat: success (hijau), warning (kuning, lebih lama), error (merah)
        function tampilkanPesan(teks, jenis = 'success') {
            const warna = {
                success: 'bg-green-50 border-green-200 text-green-800',
                warning: 'bg-amber-50 border-amber-200 text-amber-800',
                error: 'bg-red-50 border-red-200 text-red-800',
            }[jenis];
            const el = document.createElement('div');
            el.setAttribute('role', jenis === 'success' ? 'status' : 'alert');
            el.className = `p-4 rounded-xl border text-sm shadow-lg ${warna}`;
            el.textContent = teks;
            document.getElementById('pesanPersediaan').appendChild(el);
            setTimeout(() => el.remove(), jenis === 'success' ? 3500 : 8000);
        }

        // Daftar persediaan (barangList) memuat ulang data saat event ini dikirim
        const muatUlangDaftar = () => window.dispatchEvent(new CustomEvent('persediaan-diperbarui'));

        // Kirim form modal (tambah / edit / ubah stok) lewat fetch; respons JSON
        async function kirimFormPersediaan(event, modalId) {
            event.preventDefault();
            const form = event.target;
            const tombol = form.querySelector('button[type="submit"]');
            const galat = form.querySelector('[data-galat]');

            tombol.disabled = true;
            galat.classList.add('hidden');

            try {
                // FormData membawa _token, _method (PUT/PATCH), dan file foto
                const response = await fetch(form.action, { method: 'POST', headers: { 'Accept': 'application/json' }, body: new FormData(form) });
                const json = await response.json().catch(() => ({}));

                if (!response.ok) {
                    galat.textContent = json.errors ? Object.values(json.errors).flat().join(' ') : (json.message ?? 'Gagal menyimpan. Silakan coba lagi.');
                    galat.classList.remove('hidden');
                    return;
                }

                document.getElementById(modalId).classList.add('hidden');
                form.reset();
                form.querySelectorAll('[id^="compressStatus"]').forEach(el => el.textContent = '');
                tampilkanPesan(json.message);
                if (json.warning) tampilkanPesan(json.warning, 'warning');
                muatUlangDaftar();
            } catch (e) {
                galat.textContent = 'Gagal menyimpan. Periksa koneksi lalu coba lagi.';
                galat.classList.remove('hidden');
            } finally {
                tombol.disabled = false;
            }
        }

        async function hapusPersediaan(item) {
            if (!confirm(`Hapus persediaan '${item.nama_barang}' beserta fotonya?`)) return;

            try {
                const response = await fetch(@js(route('barang.destroy', ':id')).replace(':id', item.id), {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
                });
                const json = await response.json().catch(() => ({}));

                tampilkanPesan(json.message ?? 'Gagal menghapus. Silakan coba lagi.', response.ok ? 'success' : 'error');
                muatUlangDaftar();
            } catch (e) {
                tampilkanPesan('Gagal menghapus. Periksa koneksi lalu coba lagi.', 'error');
            }
        }

        // Unggah file impor; diproses di latar belakang, jadi respons hanya berarti file diterima
        async function kirimImport(event) {
            event.preventDefault();
            const form = event.target;
            const tombol = form.querySelector('button[type="submit"]');
            tombol.disabled = true;

            try {
                const response = await fetch(form.action, { method: 'POST', headers: { 'Accept': 'application/json' }, body: new FormData(form) });
                const json = await response.json().catch(() => ({}));

                if (!response.ok) {
                    tampilkanPesan(json.errors ? Object.values(json.errors).flat().join(' ') : (json.message ?? 'Gagal mengunggah file.'), 'error');
                    return;
                }

                form.reset();
                tampilkanPesan(json.message);
                window.dispatchEvent(new CustomEvent('buka-riwayat-impor'));
            } catch (e) {
                tampilkanPesan('Gagal mengunggah file. Periksa koneksi lalu coba lagi.', 'error');
            } finally {
                tombol.disabled = false;
            }
        }

        // Dialog Riwayat Impor. Jika ada impor yang baru selesai berhasil, daftar persediaan ikut dimuat ulang.
        function riwayatImport(url) {
            let berhasilTerlihat = null; // id impor berstatus success yang sudah pernah tampil

            return {
                open: false,
                loading: false,
                error: '',
                items: [],
                warnaStatus: {
                    start: 'bg-slate-100 text-slate-600',
                    processing: 'bg-blue-100 text-blue-700',
                    error: 'bg-red-100 text-red-700',
                    success: 'bg-green-100 text-green-700',
                },

                buka() {
                    this.open = true;
                    this.muat();
                },

                async muat() {
                    this.loading = true;
                    this.error = '';
                    try {
                        const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
                        if (!response.ok) throw new Error(response.status);
                        this.items = (await response.json()).data;

                        const berhasil = this.items.filter(i => i.status === 'success').map(i => i.id);
                        if (berhasilTerlihat && berhasil.some(id => !berhasilTerlihat.includes(id))) muatUlangDaftar();
                        berhasilTerlihat = berhasil;
                    } catch (e) {
                        this.error = 'Gagal memuat riwayat impor. Silakan coba lagi.';
                    } finally {
                        this.loading = false;
                    }
                },
            };
        }

        // Galat lama dari pengisian sebelumnya tidak ikut tampil saat modal dibuka lagi
        function bersihkanGalat(form) {
            form.querySelector('[data-galat]').classList.add('hidden');
        }

        function openStockModal(id, nama, stock, dipesan, satuan) {
            const form = document.getElementById('formStock');
            form.action = "{{ route('barang.update-stock', ':id') }}".replace(':id', id);
            form.reset();
            bersihkanGalat(form);
            document.getElementById('stockInfo').textContent = `${nama} — stok saat ini: ${stock} ${satuan}`
                + (dipesan > 0 ? ` (${dipesan} sudah diajukan di pengajuan pending)` : '');
            document.getElementById('modalStock').classList.remove('hidden');
        }

        function openEditModal(id, kode, nama, satuan, fotoUrl) {
            const form = document.getElementById('formEdit');
            form.action = "{{ route('barang.update', ':id') }}".replace(':id', id);
            form.reset();
            bersihkanGalat(form);
            document.getElementById('compressStatusEdit').textContent = '';
            document.getElementById('editKode').value = kode;
            document.getElementById('editNama').value = nama;
            document.getElementById('editSatuan').value = satuan;
            document.getElementById('editPreviewFoto').src = fotoUrl;
            document.getElementById('modalEdit').classList.remove('hidden');
        }

        // FUNGSI KOMPRES GAMBAR OTOMATIS DI BROWSER
        function autoCompressImage(input) {
            const file = input.files[0];
            if (!file) return;

            const originalMB = (file.size / 1024 / 1024).toFixed(2);

            // Pilih elemen status
            const statusElem = input.nextElementSibling;
            statusElem.innerText = `Mengompresi gambar (${originalMB} MB)...`;

            const reader = new FileReader();
            reader.readAsDataURL(file);
            reader.onload = function (e) {
                const img = new Image();
                img.src = e.target.result;
                img.onload = function () {
                    // Maksimal dimensi (misal max lebar/tinggi 1000px - sangat cukup untuk foto produk)
                    const maxDimension = 1000;
                    let width = img.width;
                    let height = img.height;

                    if (width > height) {
                        if (width > maxDimension) {
                            height = Math.round((height *= maxDimension / width));
                            width = maxDimension;
                        }
                    } else {
                        if (height > maxDimension) {
                            width = Math.round((width *= maxDimension / height));
                            height = maxDimension;
                        }
                    }

                    // Gambar ke Canvas
                    const canvas = document.createElement('canvas');
                    canvas.width = width;
                    canvas.height = height;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, width, height);

                    // Konversi ke File Blob (Kualitas 0.7 = 70%)
                    canvas.toBlob(function (blob) {
                        const compressedMB = (blob.size / 1024 / 1024).toFixed(2);

                        // Buat file baru dari blob terkompresi
                        const compressedFile = new File([blob], file.name, {
                            type: 'image/jpeg',
                            lastModified: Date.now()
                        });

                        // Timpa input file dengan file baru yang sudah terkompresi
                        const dataTransfer = new DataTransfer();
                        dataTransfer.items.add(compressedFile);
                        input.files = dataTransfer.files;

                        statusElem.innerText = `✓ Foto berhasil dikompresi: ${originalMB} MB ➔ ${compressedMB} MB`;
                    }, 'image/jpeg', 0.7);
                };
            };
        }
    </script>
@endsection