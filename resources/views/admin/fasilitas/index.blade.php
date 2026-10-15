@extends('layouts.admin')
@section('title', 'Fasilitas')

@section('content')
    {{-- Daftar dimuat lewat JSON (fasilitas.data): tambah / ubah / hapus tidak me-reload halaman --}}
    <div class="space-y-6" x-data="fasilitasPage(@js($initial))">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold tracking-tight text-gray-900">Fasilitas</h2>
                <p class="text-xs text-gray-400">Mobil, ruang rapat, dan fasilitas lain yang dapat dipinjam</p>
            </div>
            <button type="button" @click="bukaForm()"
                class="flex items-center justify-center gap-2 bg-gradient-to-r from-bps-blue to-bps-blue-dark hover:from-bps-blue-dark hover:to-bps-blue text-white px-4 py-2.5 rounded-xl text-sm font-semibold transition shadow-[0_8px_24px_-12px_rgba(0,61,130,0.8)] cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Tambah Fasilitas
            </button>
        </div>

        {{-- Filter jenis --}}
        <div class="flex flex-wrap gap-2">
            <button type="button" @click="pilihJenis('')"
                :class="filterType === '' ? 'bg-bps-blue text-white border-bps-blue' : 'bg-white text-slate-600 border-slate-200 hover:border-bps-blue/40'"
                class="px-3 py-1.5 rounded-full border text-xs font-semibold transition cursor-pointer">
                Semua <span class="opacity-70" x-text="`(${counts[''] ?? 0})`"></span>
            </button>
            <template x-for="type in types" :key="type.id">
                <button type="button" @click="pilihJenis(type.id)"
                    :style="filterType === type.id ? `background:${type.color};border-color:${type.color};color:#fff` : ''"
                    :class="filterType === type.id ? '' : 'bg-white text-slate-600 border-slate-200 hover:border-bps-blue/40'"
                    class="px-3 py-1.5 rounded-full border text-xs font-semibold transition cursor-pointer">
                    <span x-text="`${type.icon} ${type.name}`"></span>
                    <span class="opacity-70" x-text="`(${counts[type.id] ?? 0})`"></span>
                </button>
            </template>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            {{-- Redup saat memuat dipasang di pembungkus, bukan <tbody>: Safari tidak menggambar ulang sebagian baris tabel setelah transisi opacity pada <tbody> --}}
            <div class="overflow-x-auto transition-opacity" :class="loading && items.length && 'opacity-50'">
                <table class="w-full">
                    <thead>
                        <tr class="bg-bps-blue/5 border-b border-gray-100">
                            <th class="text-left px-6 py-4 text-xs font-bold text-bps-blue uppercase tracking-wider w-16">No</th>
                            <th class="text-left px-6 py-4 text-xs font-bold text-bps-blue uppercase tracking-wider">Nama</th>
                            <th class="text-left px-6 py-4 text-xs font-bold text-bps-blue uppercase tracking-wider">Jenis</th>
                            <th class="text-left px-6 py-4 text-xs font-bold text-bps-blue uppercase tracking-wider w-28">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <template x-for="(item, i) in items" :key="item.id">
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 text-sm text-gray-500" x-text="meta.from + i"></td>
                                {{-- Nama + kapasitas di bawahnya (hanya ruang rapat) --}}
                                <td class="px-6 py-4">
                                    <span class="text-sm font-semibold text-gray-900 block" x-text="item.name"></span>
                                    <template x-if="item.type.code === 'room'">
                                        <span class="text-xs block" :class="item.capacity ? 'text-gray-400' : 'text-amber-600 font-semibold'"
                                            x-text="item.capacity ? `Kapasitas ${item.capacity} orang` : 'Kapasitas belum diisi'"></span>
                                    </template>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold whitespace-nowrap"
                                        :style="`background:${item.type.color}14;color:${item.type.color}`"
                                        x-text="`${item.type.icon} ${item.type.name}`"></span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex gap-2">
                                        <button type="button" @click="bukaForm(item)" title="Ubah fasilitas" :aria-label="`Ubah ${item.name}`"
                                            class="p-1.5 rounded-lg bg-bps-blue/5 text-bps-blue hover:bg-bps-blue/10 transition cursor-pointer">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button type="button" @click="bukaHapus(item)" :disabled="item.dipakai"
                                            :title="item.dipakai ? 'Tidak dapat dihapus: sudah dipakai peminjaman' : 'Hapus fasilitas'" :aria-label="`Hapus ${item.name}`"
                                            class="p-1.5 rounded-lg bg-red-50 text-red-500 hover:bg-red-100 transition cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-red-50">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <tr x-show="loading && !items.length">
                            <td colspan="4" class="px-6 py-12 text-center text-gray-400 text-sm">Memuat data fasilitas...</td>
                        </tr>
                        <tr x-show="!loading && !error && !items.length" x-cloak>
                            <td colspan="4" class="px-6 py-12 text-center text-gray-400 text-sm">Belum ada fasilitas.</td>
                        </tr>
                        <tr x-show="error" x-cloak>
                            <td colspan="4" class="px-6 py-12 text-center text-sm text-red-600">
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

        {{-- Modal Tambah / Ubah Fasilitas --}}
        <div x-show="form.open" x-cloak class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
            role="dialog" aria-modal="true" aria-labelledby="judulFormFasilitas" @keydown.escape.window="form.open = false">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 max-h-[90dvh] overflow-y-auto" @click.outside="form.open = false">
                <div class="flex items-center justify-between mb-5">
                    <h3 id="judulFormFasilitas" class="text-lg font-bold text-gray-900" x-text="form.id ? 'Ubah Fasilitas' : 'Tambah Fasilitas'"></h3>
                    <button type="button" @click="form.open = false" aria-label="Tutup" class="text-gray-400 hover:text-gray-600 cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <form @submit.prevent="simpan()" class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Jenis Fasilitas</label>
                        <div class="grid grid-cols-[repeat(auto-fit,minmax(100px,1fr))] gap-2">
                            <template x-for="type in types" :key="type.id">
                                <label class="flex flex-col items-center gap-1 p-3 rounded-xl border-2 transition"
                                    :class="[form.facility_type_id === type.id ? 'shadow-sm' : 'opacity-60', form.dipakai ? 'cursor-not-allowed' : 'cursor-pointer']"
                                    :style="`border-color:${form.facility_type_id === type.id ? type.color : type.color + '33'};background:${type.color}12`">
                                    <input type="radio" class="sr-only" :value="type.id" x-model="form.facility_type_id" :disabled="form.dipakai" @change="form.errors.facility_type_id = null">
                                    <span class="text-2xl" x-text="type.icon"></span>
                                    <span class="text-xs font-bold text-center" :style="`color:${type.color}`" x-text="type.name"></span>
                                </label>
                            </template>
                        </div>
                        <p x-show="form.dipakai" class="text-[11px] text-gray-400 mt-1">Jenis tidak dapat diubah karena fasilitas ini sudah dipakai peminjaman.</p>
                        <p x-show="form.errors.facility_type_id" class="text-xs text-red-500 font-semibold mt-1" x-text="form.errors.facility_type_id?.[0]"></p>
                    </div>

                    <div>
                        <label for="fasilitas_nama" class="block text-sm font-semibold text-gray-700 mb-1.5" x-text="jenisTerpilih?.code === 'car' ? 'Nama / Plat Nomor' : 'Nama Fasilitas'"></label>
                        <input id="fasilitas_nama" type="text" x-model="form.name" required maxlength="255" @input="form.errors.name = null"
                            :placeholder="jenisTerpilih?.code === 'car' ? 'Contoh: L 1760 HP' : (jenisTerpilih?.code === 'room' ? 'Contoh: Ruang Aula Majapahit' : 'Nama fasilitas')"
                            :class="form.errors.name ? 'border-red-500' : 'border-gray-200'"
                            class="w-full px-4 py-2.5 rounded-xl border text-sm focus:outline-none focus:ring-2 focus:ring-bps-blue">
                        <p x-show="form.errors.name" class="text-xs text-red-500 font-semibold mt-1" x-text="form.errors.name?.[0]"></p>
                    </div>

                    {{-- Kapasitas: hanya ruang rapat --}}
                    <template x-if="jenisTerpilih?.code === 'room'">
                        <div>
                            <label for="fasilitas_kapasitas" class="block text-sm font-semibold text-gray-700 mb-1.5">Kapasitas (orang)</label>
                            <input id="fasilitas_kapasitas" type="number" x-model="form.capacity" required min="1" max="65535" placeholder="Contoh: 30" @input="form.errors.capacity = null"
                                :class="form.errors.capacity ? 'border-red-500' : 'border-gray-200'"
                                class="w-full px-4 py-2.5 rounded-xl border text-sm focus:outline-none focus:ring-2 focus:ring-bps-blue">
                            <p x-show="form.errors.capacity" class="text-xs text-red-500 font-semibold mt-1" x-text="form.errors.capacity?.[0]"></p>
                            <p class="text-[11px] text-gray-400 mt-1">Peminjaman ruang ini tidak boleh melebihi kapasitas.</p>
                        </div>
                    </template>

                    <p x-show="form.error" x-cloak role="alert" class="p-3 rounded-xl bg-red-50 border border-red-200 text-xs text-red-700" x-text="form.error"></p>
                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="form.open = false" class="flex-1 px-4 py-2.5 rounded-xl border border-gray-200 text-sm font-semibold text-gray-600 hover:bg-gray-50 cursor-pointer">Batal</button>
                        <button type="submit" :disabled="form.saving || !form.facility_type_id"
                            class="flex-1 px-4 py-2.5 rounded-xl bg-bps-blue text-white text-sm font-semibold hover:bg-bps-blue-dark transition cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed"
                            x-text="form.saving ? 'Menyimpan...' : 'Simpan'"></button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Modal Hapus Fasilitas --}}
        <div x-show="hapus.item" x-cloak class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
            role="dialog" aria-modal="true" aria-labelledby="judulHapusFasilitas" @keydown.escape.window="hapus.item = null">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6" @click.outside="hapus.item = null">
                <div class="flex items-start gap-3 mb-4">
                    <div class="w-10 h-10 shrink-0 rounded-xl bg-red-50 text-red-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <h3 id="judulHapusFasilitas" class="text-lg font-bold text-gray-900">Hapus Fasilitas?</h3>
                        <p class="text-xs text-gray-500 mt-1">
                            <span class="font-semibold text-gray-700" x-text="hapus.item?.name"></span> tidak dapat dipilih lagi saat meminjam.
                        </p>
                    </div>
                </div>
                <p x-show="hapus.error" x-cloak role="alert" class="mb-4 p-3 rounded-xl bg-red-50 border border-red-200 text-xs text-red-700" x-text="hapus.error"></p>
                <div class="flex gap-3">
                    <button type="button" @click="hapus.item = null"
                        class="flex-1 px-4 py-2.5 rounded-xl border border-gray-200 text-sm font-semibold text-gray-600 hover:bg-gray-50 cursor-pointer">Batal</button>
                    <button type="button" @click="kirimHapus()" :disabled="hapus.saving"
                        class="flex-1 px-4 py-2.5 rounded-xl bg-gradient-to-r from-red-500 to-red-600 text-white text-sm font-semibold transition cursor-pointer disabled:opacity-60 disabled:cursor-wait">Ya, Hapus</button>
                </div>
            </div>
        </div>

        {{-- Pesan singkat di pojok kanan bawah --}}
        <div aria-live="polite" class="fixed bottom-4 right-4 left-4 sm:left-auto sm:w-96 z-[60] space-y-2">
            <template x-for="pesan in pesanList" :key="pesan.id">
                <div role="status" class="p-4 rounded-xl border text-sm shadow-lg bg-green-50 border-green-200 text-green-800" x-text="pesan.text"></div>
            </template>
        </div>
    </div>

    <script>
        function fasilitasPage(config) {
            let requestSeq = 0; // respons lama yang datang terlambat diabaikan
            const formKosong = { open: false, id: null, facility_type_id: '', name: '', capacity: '', dipakai: false, saving: false, error: '', errors: {} };

            const kirimJson = (url, method, body) => fetch(url, {
                method,
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': config.csrf },
                body: body ? JSON.stringify(body) : undefined,
            });

            return {
                types: config.types,
                items: [],
                meta: { current_page: 1, last_page: 1, from: null, to: null, total: 0 },
                counts: {}, // jumlah fasilitas per jenis ('' = semua)
                filterType: config.type_id,
                page: config.page,
                loading: true,
                error: '',
                form: { ...formKosong },
                hapus: { item: null, saving: false, error: '' },
                pesanList: [],

                init() {
                    this.load();
                },

                get jenisTerpilih() {
                    return this.types.find(t => t.id === this.form.facility_type_id) ?? null;
                },

                // Tanpa argumen: muat ulang halaman yang sedang dibuka (dipakai juga setelah tambah / ubah / hapus)
                async load(page = this.page) {
                    const seq = ++requestSeq;
                    this.loading = true;
                    this.error = '';

                    const params = new URLSearchParams({ page });
                    if (this.filterType) params.set('type_id', this.filterType);

                    try {
                        const response = await fetch(`${config.dataUrl}?${params}`, { headers: { 'Accept': 'application/json' } });
                        if (!response.ok) throw new Error(response.status);
                        const json = await response.json();
                        if (seq !== requestSeq) return;

                        // Halaman di luar jangkauan (mis. setelah hapus baris terakhir di halaman terakhir): pindah ke halaman terakhir
                        if (json.meta.current_page > json.meta.last_page && json.meta.total > 0) {
                            this.load(json.meta.last_page);
                            return;
                        }

                        this.items = json.data;
                        this.meta = json.meta;
                        this.counts = json.meta.counts;
                        this.page = json.meta.current_page;
                        this.syncUrl();
                    } catch (e) {
                        if (seq === requestSeq) this.error = 'Gagal memuat data fasilitas. Silakan coba lagi.';
                    } finally {
                        if (seq === requestSeq) this.loading = false;
                    }
                },

                // Ganti filter jenis selalu mulai dari halaman pertama
                pilihJenis(typeId) {
                    this.filterType = typeId;
                    this.load(1);
                },

                goTo(page) {
                    if (page >= 1 && page <= this.meta.last_page && page !== this.meta.current_page) this.load(page);
                },

                // Nomor halaman: pertama, terakhir, dan sekitar halaman aktif; sisanya diringkas jadi "…"
                get pages() {
                    const { current_page: current, last_page: last } = this.meta;
                    const pages = [];
                    for (let p = 1; p <= last; p++) {
                        if (p === 1 || p === last || Math.abs(p - current) <= 1) pages.push(p);
                        else if (pages[pages.length - 1] !== '…') pages.push('…');
                    }
                    return pages;
                },

                // Simpan kondisi daftar di URL agar tetap sama setelah reload
                syncUrl() {
                    const params = new URLSearchParams();
                    if (this.filterType) params.set('type_id', this.filterType);
                    if (this.page > 1) params.set('page', this.page);
                    history.replaceState(null, '', params.size ? `${location.pathname}?${params}` : location.pathname);
                },

                tampilkanPesan(text) {
                    const id = Date.now() + Math.random();
                    this.pesanList.push({ id, text });
                    setTimeout(() => this.pesanList = this.pesanList.filter(p => p.id !== id), 3500);
                },

                // item kosong = tambah baru (jenis mengikuti filter yang aktif); item = ubah fasilitas tersebut
                bukaForm(item = null) {
                    this.form = {
                        ...formKosong,
                        errors: {},
                        open: true,
                        id: item?.id ?? null,
                        facility_type_id: item?.facility_type_id ?? this.filterType,
                        name: item?.name ?? '',
                        capacity: item?.capacity ?? '',
                        dipakai: item?.dipakai ?? false,
                    };
                },

                async simpan() {
                    this.form.saving = true;
                    this.form.error = '';
                    this.form.errors = {};
                    try {
                        const response = await kirimJson(
                            this.form.id ? config.updateUrl.replace(':id', this.form.id) : config.storeUrl,
                            this.form.id ? 'PUT' : 'POST',
                            {
                                facility_type_id: this.form.facility_type_id,
                                name: this.form.name,
                                capacity: this.jenisTerpilih?.code === 'room' ? this.form.capacity : null,
                            },
                        );
                        const json = await response.json().catch(() => ({}));
                        if (response.status === 422 && json.errors) {
                            this.form.errors = json.errors;
                            return;
                        }
                        if (!response.ok) {
                            this.form.error = response.status === 404
                                ? 'Fasilitas ini sudah dihapus sebelumnya.'
                                : (json.message ?? 'Gagal menyimpan fasilitas. Silakan coba lagi.');
                            return;
                        }
                        this.form.open = false;
                        this.tampilkanPesan(json.message);
                        this.load();
                    } catch (e) {
                        this.form.error = 'Gagal menyimpan fasilitas. Periksa koneksi lalu coba lagi.';
                    } finally {
                        this.form.saving = false;
                    }
                },

                bukaHapus(item) {
                    this.hapus = { item, saving: false, error: '' };
                },

                async kirimHapus() {
                    this.hapus.saving = true;
                    this.hapus.error = '';
                    try {
                        const response = await kirimJson(config.destroyUrl.replace(':id', this.hapus.item.id), 'DELETE');
                        const json = await response.json().catch(() => ({}));
                        this.load();
                        if (!response.ok) {
                            this.hapus.error = response.status === 404
                                ? 'Fasilitas ini sudah dihapus sebelumnya.'
                                : (json.message ?? 'Gagal menghapus. Silakan coba lagi.');
                            return;
                        }
                        this.hapus.item = null;
                        this.tampilkanPesan(json.message);
                    } catch (e) {
                        this.hapus.error = 'Gagal menghapus. Periksa koneksi lalu coba lagi.';
                    } finally {
                        this.hapus.saving = false;
                    }
                },
            };
        }
    </script>
@endsection
