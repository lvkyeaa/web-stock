@extends('layouts.admin')
@section('title', 'Pengguna')

@section('content')
    {{-- Daftar dimuat lewat JSON (manajemen-user.data): cari / ganti halaman / tambah / ubah / hapus tidak me-reload halaman --}}
    <div class="space-y-6" x-data="penggunaPage(@js($initial))">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold tracking-tight text-gray-900">Pengguna</h2>
            <div class="flex flex-wrap items-center justify-end gap-2">
                <a href="{{ route('admin.manajemen-user.ketua-tim.index') }}"
                    class="flex items-center gap-2 border border-bps-blue/30 bg-white text-bps-blue hover:bg-bps-blue/5 px-4 py-2.5 rounded-xl text-sm font-semibold transition cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Kelola Ketua Tim/Penanggung Jawab
                </a>
                <button type="button" @click="bukaForm()"
                    class="flex items-center gap-2 bg-gradient-to-r from-bps-blue to-bps-blue-dark hover:from-bps-blue-dark hover:to-bps-blue text-white px-4 py-2.5 rounded-xl text-sm font-semibold transition shadow-[0_8px_24px_-12px_rgba(0,61,130,0.8)] cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Tambah Pengguna
                </button>
            </div>
        </div>

        {{-- Cari nama / username (debounce) --}}
        <form @submit.prevent="search()" class="rounded-2xl border border-slate-200/70 bg-white p-3 shadow-sm">
            <div class="relative">
                <input type="text" x-model="filters.q" @input.debounce.400ms="search()" placeholder="Cari nama atau username..."
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
        </form>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-bps-blue/5 border-b border-gray-100">
                            <th class="text-left px-6 py-4 text-xs font-bold text-bps-blue uppercase tracking-wider">No</th>
                            <th class="text-left px-6 py-4 text-xs font-bold text-bps-blue uppercase tracking-wider">Nama</th>
                            <th class="text-left px-6 py-4 text-xs font-bold text-bps-blue uppercase tracking-wider">Role</th>
                            <th class="text-left px-6 py-4 text-xs font-bold text-bps-blue uppercase tracking-wider">Dibuat</th>
                            <th class="text-left px-6 py-4 text-xs font-bold text-bps-blue uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <template x-for="(user, i) in items" :key="user.id">
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 text-sm text-gray-500" x-text="meta.from + i"></td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-xl flex items-center justify-center" :class="user.role === 'admin' ? 'bg-bps-blue' : 'bg-bps-green'">
                                            <span class="text-white text-xs font-bold uppercase" x-text="(user.name || user.username).charAt(0)"></span>
                                        </div>
                                        <div>
                                            <span class="text-sm font-semibold text-gray-900 block" x-text="user.name || '-'"></span>
                                            <span class="text-xs text-gray-400 block" x-text="user.username"></span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-bold"
                                        :class="user.role === 'admin' ? 'bg-bps-blue/10 text-bps-blue' : 'bg-bps-green/10 text-bps-green'"
                                        x-text="user.role_label"></span>
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-500" x-text="user.dibuat"></td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <button type="button" @click="bukaForm(user)"
                                            class="p-1.5 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 transition cursor-pointer"
                                            title="Edit user" :aria-label="`Edit ${user.username}`">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button type="button" x-show="!user.is_self" @click="bukaHapus(user)"
                                            class="p-1.5 rounded-lg bg-red-50 text-red-500 hover:bg-red-100 transition cursor-pointer"
                                            title="Hapus user" :aria-label="`Hapus ${user.username}`">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                        <span x-show="user.is_self" class="text-xs text-gray-400 italic px-1">Anda</span>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <tr x-show="loading && !items.length">
                            <td colspan="5" class="px-6 py-12 text-center text-gray-400 text-sm">Memuat data pengguna...</td>
                        </tr>
                        <tr x-show="!loading && !error && !items.length" x-cloak>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-400 text-sm"
                                x-text="filters.q.trim() ? 'Tidak ada pengguna yang cocok dengan pencarian.' : 'Belum ada user.'"></td>
                        </tr>
                        <tr x-show="error" x-cloak>
                            <td colspan="5" class="px-6 py-12 text-center text-sm text-red-600">
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

        {{-- Modal Tambah / Edit Pengguna --}}
        <div x-show="form.open" x-cloak class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
            role="dialog" aria-modal="true" aria-labelledby="judulFormUser" @keydown.escape.window="form.open = false">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 max-h-full overflow-y-auto" @click.outside="form.open = false">
                <div class="flex items-center justify-between mb-5">
                    <h3 id="judulFormUser" class="text-lg font-bold text-gray-900" x-text="form.id ? 'Edit Data Pengguna' : 'Tambah Pengguna'"></h3>
                    <button type="button" @click="form.open = false" aria-label="Tutup" class="text-gray-400 hover:text-gray-600 cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <form @submit.prevent="simpan()" class="space-y-4">
                    <div>
                        <label for="user_name" class="block text-sm font-semibold text-gray-700 mb-1.5">Nama Lengkap</label>
                        <input type="text" id="user_name" x-model="form.name" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-bps-blue">
                    </div>
                    <div>
                        <label for="user_email" class="block text-sm font-semibold text-gray-700 mb-1.5">Email</label>
                        <input type="email" id="user_email" x-model="form.email" required placeholder="nama@mail.com" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-bps-blue">
                        <p class="text-[11px] text-gray-400 mt-1">Email juga dipakai sebagai username untuk login.</p>
                    </div>
                    <div x-show="!form.id">
                        <label for="user_password" class="block text-sm font-semibold text-gray-700 mb-1.5">Password</label>
                        <input type="password" id="user_password" x-model="form.password" :required="!form.id" minlength="6" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-bps-blue">
                    </div>
                    <div>
                        <label for="user_role" class="block text-sm font-semibold text-gray-700 mb-1.5">Role</label>
                        <select id="user_role" x-model="form.role" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-bps-blue bg-white">
                            <option value="customer">Pengguna</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>

                    {{-- Ubah password saat edit (opsional) --}}
                    <div x-show="form.id" class="pt-2 border-t border-gray-100 space-y-3">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Ubah Password (Opsional)</p>
                        <div>
                            <label for="user_password_baru" class="block text-xs font-medium text-gray-600 mb-1">Password Baru</label>
                            <input type="password" id="user_password_baru" x-model="form.password" minlength="6" placeholder="Kosongkan jika tidak diubah" class="w-full px-4 py-2 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-bps-blue">
                        </div>
                        <div>
                            <label for="user_password_konfirmasi" class="block text-xs font-medium text-gray-600 mb-1">Konfirmasi Password Baru</label>
                            <input type="password" id="user_password_konfirmasi" x-model="form.password_confirmation" placeholder="Ulangi password baru" class="w-full px-4 py-2 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-bps-blue">
                        </div>
                    </div>

                    <p x-show="form.error" x-cloak role="alert" class="p-3 rounded-xl bg-red-50 border border-red-200 text-xs text-red-700" x-text="form.error"></p>

                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="form.open = false" class="flex-1 px-4 py-2.5 rounded-xl border border-gray-200 text-sm font-semibold text-gray-600 hover:bg-gray-50 cursor-pointer">Batal</button>
                        <button type="submit" :disabled="form.saving"
                            class="flex-1 px-4 py-2.5 rounded-xl bg-bps-blue text-white text-sm font-semibold hover:bg-bps-blue-dark transition cursor-pointer disabled:opacity-60 disabled:cursor-wait"
                            x-text="form.saving ? 'Menyimpan...' : (form.id ? 'Simpan Perubahan' : 'Simpan')"></button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Modal Hapus Pengguna --}}
        <div x-show="hapus.item" x-cloak class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
            role="dialog" aria-modal="true" aria-labelledby="judulHapusUser" @keydown.escape.window="hapus.item = null">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6" @click.outside="hapus.item = null">
                <div class="flex items-start gap-3 mb-4">
                    <div class="w-10 h-10 shrink-0 rounded-xl bg-red-50 text-red-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <h3 id="judulHapusUser" class="text-lg font-bold text-gray-900">Hapus Pengguna?</h3>
                        <p class="text-xs text-gray-500 mt-1">
                            Pengguna <span class="font-semibold text-gray-700" x-text="hapus.item ? `${hapus.item.name} (${hapus.item.username})` : ''"></span> akan dihapus permanen.
                            Pengguna yang masih punya peminjaman, atau sudah punya pengajuan / riwayat persetujuan, tidak bisa dihapus.
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
        function penggunaPage(config) {
            let requestSeq = 0; // respons lama yang datang terlambat diabaikan
            let kataDiminta = null; // pencarian dari permintaan terakhir

            const kirimJson = (url, method, body) => fetch(url, {
                method,
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': config.csrf },
                body: body ? JSON.stringify(body) : undefined,
            });
            const pesanGalat = (json, cadangan) => json.errors ? Object.values(json.errors).flat().join(' ') : (json.message ?? cadangan);
            const formKosong = { open: false, id: null, name: '', email: '', password: '', password_confirmation: '', role: 'customer', saving: false, error: '' };

            return {
                items: [],
                meta: { current_page: 1, last_page: 1, from: null, to: null, total: 0 },
                filters: { q: config.q },
                page: config.page,
                loading: true,
                error: '',
                form: { ...formKosong },
                hapus: { item: null, saving: false, error: '' },
                pesanList: [],

                init() {
                    this.load();
                },

                async load(page = this.page) {
                    const seq = ++requestSeq;
                    kataDiminta = this.filters.q.trim();
                    this.loading = true;
                    this.error = '';

                    const params = new URLSearchParams({ page });
                    if (this.filters.q.trim()) params.set('q', this.filters.q.trim());

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
                        this.page = json.meta.current_page;
                        this.syncUrl();
                    } catch (e) {
                        if (seq === requestSeq) this.error = 'Gagal memuat data pengguna. Silakan coba lagi.';
                    } finally {
                        if (seq === requestSeq) this.loading = false;
                    }
                },

                // Pencarian baru selalu mulai dari halaman pertama; dilewati jika sama dengan yang terakhir diminta
                search() {
                    if (!this.error && this.filters.q.trim() === kataDiminta) return;
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
                    if (this.page > 1) params.set('page', this.page);
                    history.replaceState(null, '', params.size ? `${location.pathname}?${params}` : location.pathname);
                },

                tampilkanPesan(text) {
                    const id = Date.now() + Math.random();
                    this.pesanList.push({ id, text });
                    setTimeout(() => this.pesanList = this.pesanList.filter(p => p.id !== id), 3500);
                },

                // Tanpa argumen: form tambah; dengan user: form edit berisi data user tersebut
                bukaForm(user = null) {
                    this.form = user
                        ? { ...formKosong, open: true, id: user.id, name: user.name ?? '', email: user.email ?? user.username, role: user.role ?? 'customer' }
                        : { ...formKosong, open: true };
                },

                async simpan() {
                    const { id, name, email, password, password_confirmation, role } = this.form;
                    this.form.saving = true;
                    this.form.error = '';
                    try {
                        const response = id
                            ? await kirimJson(config.updateUrl.replace(':id', id), 'PUT', { name, email, role, password, password_confirmation })
                            : await kirimJson(config.storeUrl, 'POST', { name, email, role, password });
                        const json = await response.json().catch(() => ({}));
                        if (!response.ok) {
                            this.form.error = pesanGalat(json, 'Gagal menyimpan pengguna. Silakan coba lagi.');
                            return;
                        }
                        this.form.open = false;
                        this.tampilkanPesan(json.message);
                        this.load(id ? this.page : 1); // pengguna baru tampil di atas (urutan terbaru)
                    } catch (e) {
                        this.form.error = 'Gagal menyimpan pengguna. Periksa koneksi lalu coba lagi.';
                    } finally {
                        this.form.saving = false;
                    }
                },

                bukaHapus(user) {
                    this.hapus = { item: user, saving: false, error: '' };
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
                                ? 'Pengguna ini sudah dihapus sebelumnya.'
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
