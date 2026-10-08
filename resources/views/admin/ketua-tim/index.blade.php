@extends('layouts.admin')
@section('title', 'Ketua Tim/Penanggung Jawab')

@section('content')
    {{-- Daftar dimuat lewat JSON (ketua-tim.data): ganti halaman / filter / tambah / hapus tidak me-reload halaman --}}
    <div class="space-y-6" x-data="ketuaTimPage(@js($initial))">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('admin.manajemen-user.index') }}" class="text-xs font-semibold text-slate-400 hover:text-bps-blue">‹ Kembali ke Pengguna</a>
                <h2 class="mt-1 text-xl font-semibold tracking-tight text-gray-900">Ketua Tim/Penanggung Jawab</h2>
            </div>
            <button type="button" @click="bukaTambah()"
                class="flex items-center justify-center gap-2 bg-gradient-to-r from-bps-blue to-bps-blue-dark hover:from-bps-blue-dark hover:to-bps-blue text-white px-4 py-2.5 rounded-xl text-sm font-semibold transition shadow-[0_8px_24px_-12px_rgba(0,61,130,0.8)] cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Tambah Ketua Tim/PJ
            </button>
        </div>

        {{-- Filter: cari (debounce) & tim --}}
        <form @submit.prevent="search()"
            class="rounded-2xl border border-slate-200/70 bg-white p-3 shadow-sm flex flex-col md:flex-row md:items-center gap-3">
            <div class="relative flex-1 min-w-0">
                <input type="text" x-model="filters.q" @input.debounce.400ms="search()" placeholder="Cari tim, nama, atau username ketua..."
                    class="w-full pl-9 pr-9 py-2 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-bps-blue">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <button type="button" x-show="filters.q" x-cloak @click="filters.q = ''; search()" title="Hapus pencarian" aria-label="Hapus pencarian"
                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <label class="flex items-center gap-2 text-[11px] font-semibold text-slate-500 whitespace-nowrap">
                Tim
                <select x-model="filters.team_id" @change="search()"
                    class="py-2 pl-3 pr-8 rounded-xl border border-slate-200 bg-white text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-bps-blue cursor-pointer max-w-[16rem]">
                    <option value="">Semua</option>
                    <template x-for="team in teams" :key="team.id">
                        <option :value="team.id" x-text="team.name" :selected="team.id === filters.team_id"></option>
                    </template>
                </select>
            </label>
        </form>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-bps-blue/5 border-b border-gray-100">
                            <th class="text-left px-6 py-4 text-xs font-bold text-bps-blue uppercase tracking-wider w-16">No</th>
                            <th class="text-left px-6 py-4 text-xs font-bold text-bps-blue uppercase tracking-wider">Tim</th>
                            <th class="text-left px-6 py-4 text-xs font-bold text-bps-blue uppercase tracking-wider">Ketua Tim/Penanggung Jawab</th>
                            <th class="text-left px-6 py-4 text-xs font-bold text-bps-blue uppercase tracking-wider w-24">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <template x-for="(item, i) in items" :key="item.id">
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 text-sm text-gray-500" x-text="meta.from + i"></td>
                                <td class="px-6 py-4">
                                    <span class="text-sm font-semibold text-gray-900 block" x-text="item.team_name"></span>
                                    <span class="text-xs text-gray-400 block" x-text="item.team_short_name || '-'"></span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm font-semibold text-gray-900 block" x-text="item.user_name"></span>
                                    <span class="text-xs text-gray-400 block" x-text="item.username"></span>
                                </td>
                                <td class="px-6 py-4">
                                    <button type="button" @click="bukaHapus(item)" title="Hapus ketua tim" :aria-label="`Hapus ${item.user_name} dari ${item.team_name}`"
                                        class="p-1.5 rounded-lg bg-red-50 text-red-500 hover:bg-red-100 transition cursor-pointer">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        </template>

                        <tr x-show="loading && !items.length">
                            <td colspan="4" class="px-6 py-12 text-center text-gray-400 text-sm">Memuat data ketua tim...</td>
                        </tr>
                        <tr x-show="!loading && !error && !items.length" x-cloak>
                            <td colspan="4" class="px-6 py-12 text-center text-gray-400 text-sm">Belum ada ketua tim.</td>
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

        {{-- Modal Tambah Ketua Tim --}}
        <div x-show="tambah.open" x-cloak class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
            role="dialog" aria-modal="true" aria-labelledby="judulTambahKetua" @keydown.escape.window="tambah.open = false">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6" @click.outside="tambah.open = false">
                <div class="flex items-center justify-between mb-5">
                    <h3 id="judulTambahKetua" class="text-lg font-bold text-gray-900">Tambah Ketua Tim/PJ</h3>
                    <button type="button" @click="tambah.open = false" aria-label="Tutup" class="text-gray-400 hover:text-gray-600 cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <form @submit.prevent="simpan()" class="space-y-4">
                    <div>
                        <label for="tambah_team_id" class="block text-sm font-semibold text-gray-700 mb-1.5">Tim</label>
                        <select id="tambah_team_id" x-model="tambah.team_id" required
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-bps-blue bg-white">
                            <option value="">-- Pilih Tim --</option>
                            <template x-for="team in teams" :key="team.id">
                                <option :value="team.id" x-text="team.name"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label for="tambah_user_id" class="block text-sm font-semibold text-gray-700 mb-1.5">Ketua Tim/Penanggung Jawab</label>
                        <select id="tambah_user_id" x-model="tambah.user_id" required
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-bps-blue bg-white">
                            <option value="">-- Pilih Pegawai --</option>
                            <template x-for="user in users" :key="user.id">
                                <option :value="user.id" x-text="`${user.name} (${user.username})`"></option>
                            </template>
                        </select>
                    </div>
                    <p x-show="tambah.error" x-cloak role="alert" class="p-3 rounded-xl bg-red-50 border border-red-200 text-xs text-red-700" x-text="tambah.error"></p>
                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="tambah.open = false" class="flex-1 px-4 py-2.5 rounded-xl border border-gray-200 text-sm font-semibold text-gray-600 hover:bg-gray-50 cursor-pointer">Batal</button>
                        <button type="submit" :disabled="tambah.saving"
                            class="flex-1 px-4 py-2.5 rounded-xl bg-bps-blue text-white text-sm font-semibold hover:bg-bps-blue-dark transition cursor-pointer disabled:opacity-60 disabled:cursor-wait"
                            x-text="tambah.saving ? 'Menyimpan...' : 'Simpan'"></button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Modal Hapus Ketua Tim --}}
        <div x-show="hapus.item" x-cloak class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
            role="dialog" aria-modal="true" aria-labelledby="judulHapusKetua" @keydown.escape.window="hapus.item = null">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6" @click.outside="hapus.item = null">
                <div class="flex items-start gap-3 mb-4">
                    <div class="w-10 h-10 shrink-0 rounded-xl bg-red-50 text-red-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <h3 id="judulHapusKetua" class="text-lg font-bold text-gray-900">Hapus Ketua Tim?</h3>
                        <p class="text-xs text-gray-500 mt-1">
                            <span class="font-semibold text-gray-700" x-text="hapus.item?.user_name"></span> tidak lagi menjadi ketua tim
                            <span class="font-semibold text-gray-700" x-text="hapus.item?.team_name"></span>.
                            Pengajuan yang sudah dibuat tidak berubah.
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
        function ketuaTimPage(config) {
            let requestSeq = 0; // respons lama yang datang terlambat diabaikan
            let kriteriaDiminta = null; // pencarian + filter dari permintaan terakhir
            const kriteria = (filters) => JSON.stringify([filters.q.trim(), filters.team_id]);

            const kirimJson = (url, method, body) => fetch(url, {
                method,
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': config.csrf },
                body: body ? JSON.stringify(body) : undefined,
            });
            const pesanGalat = (json, cadangan) => json.errors ? Object.values(json.errors).flat().join(' ') : (json.message ?? cadangan);

            return {
                teams: config.teams,
                users: config.users,
                items: [],
                meta: { current_page: 1, last_page: 1, from: null, to: null, total: 0 },
                filters: { q: config.q, team_id: config.team_id },
                page: config.page,
                loading: true,
                error: '',
                tambah: { open: false, team_id: '', user_id: '', saving: false, error: '' },
                hapus: { item: null, saving: false, error: '' },
                pesanList: [],

                init() {
                    this.load();
                },

                async load(page = this.page) {
                    const seq = ++requestSeq;
                    kriteriaDiminta = kriteria(this.filters);
                    this.loading = true;
                    this.error = '';

                    const params = new URLSearchParams({ page });
                    if (this.filters.q.trim()) params.set('q', this.filters.q.trim());
                    if (this.filters.team_id) params.set('team_id', this.filters.team_id);

                    try {
                        const response = await fetch(`${config.dataUrl}?${params}`, { headers: { 'Accept': 'application/json' } });
                        if (!response.ok) throw new Error(response.status);
                        const json = await response.json();
                        if (seq !== requestSeq) return;

                        // Halaman di luar jangkauan (mis. setelah hapus baris terakhir di halaman terakhir): pindah ke halaman terakhir
                        if (json.meta.current_page > json.meta.last_page && json.meta.last_page >= 1 && json.meta.total > 0) {
                            this.load(json.meta.last_page);
                            return;
                        }

                        this.items = json.data;
                        this.meta = json.meta;
                        this.page = json.meta.current_page;
                        this.syncUrl();
                    } catch (e) {
                        if (seq === requestSeq) this.error = 'Gagal memuat data ketua tim. Silakan coba lagi.';
                    } finally {
                        if (seq === requestSeq) this.loading = false;
                    }
                },

                // Pencarian & filter baru selalu mulai dari halaman pertama; dilewati jika kriteria sama dengan yang terakhir diminta
                search() {
                    if (!this.error && kriteria(this.filters) === kriteriaDiminta) return;
                    return this.load(1);
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
                    if (this.filters.q.trim()) params.set('q', this.filters.q.trim());
                    if (this.filters.team_id) params.set('team_id', this.filters.team_id);
                    if (this.page > 1) params.set('page', this.page);
                    history.replaceState(null, '', params.size ? `${location.pathname}?${params}` : location.pathname);
                },

                tampilkanPesan(text) {
                    const id = Date.now() + Math.random();
                    this.pesanList.push({ id, text });
                    setTimeout(() => this.pesanList = this.pesanList.filter(p => p.id !== id), 3500);
                },

                // Tim di form tambah mengikuti filter tim yang sedang aktif
                bukaTambah() {
                    this.tambah = { open: true, team_id: this.filters.team_id, user_id: '', saving: false, error: '' };
                },

                async simpan() {
                    this.tambah.saving = true;
                    this.tambah.error = '';
                    try {
                        const response = await kirimJson(config.storeUrl, 'POST', { team_id: this.tambah.team_id, user_id: this.tambah.user_id });
                        const json = await response.json().catch(() => ({}));
                        if (!response.ok) {
                            this.tambah.error = pesanGalat(json, 'Gagal menambahkan ketua tim. Silakan coba lagi.');
                            return;
                        }
                        this.tambah.open = false;
                        this.tampilkanPesan(json.message);
                        this.load();
                    } catch (e) {
                        this.tambah.error = 'Gagal menambahkan ketua tim. Periksa koneksi lalu coba lagi.';
                    } finally {
                        this.tambah.saving = false;
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
                                ? 'Data ini sudah dihapus sebelumnya.'
                                : pesanGalat(json, 'Gagal menghapus. Silakan coba lagi.');
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
