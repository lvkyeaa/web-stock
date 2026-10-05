<script>
    // Daftar pengajuan (Pengajuan Saya & persetujuan admin): dimuat lewat JSON (pengajuan.data) tanpa reload halaman
    function pengajuanList(config) {
        let requestSeq = 0; // respons lama yang datang terlambat diabaikan
        let kriteriaDiminta = null; // pencarian + filter dari permintaan terakhir (termasuk yang masih berjalan)
        const kriteria = (filters) => JSON.stringify([filters.q.trim(), filters.status]);

        return {
            items: [],
            meta: { current_page: 1, last_page: 1, from: null, to: null, total: 0 },
            filters: { q: config.q, status: config.status },
            page: config.page,
            loading: true,
            error: '',
            warnaStatus: {
                pending: 'bg-amber-50 text-amber-700 border border-amber-200',
                disetujui: 'bg-emerald-50 text-emerald-700 border border-emerald-200',
                ditolak: 'bg-rose-50 text-rose-700 border border-rose-200',
                dibatalkan: 'bg-slate-100 text-slate-500 border border-slate-200',
            },

            init() {
                this.load();
            },

            async load(page = this.page) {
                const seq = ++requestSeq;
                kriteriaDiminta = kriteria(this.filters);
                this.loading = true;
                this.error = '';

                const params = new URLSearchParams({ page, lingkup: config.lingkup });
                if (this.filters.q.trim()) params.set('q', this.filters.q.trim());
                if (this.filters.status) params.set('status', this.filters.status);

                try {
                    const response = await fetch(`${config.dataUrl}?${params}`, { headers: { 'Accept': 'application/json' } });
                    if (!response.ok) throw new Error(response.status);
                    const json = await response.json();
                    if (seq !== requestSeq) return;

                    // Halaman di luar jangkauan (mis. setelah filter berubah dari URL lama): pindah ke halaman terakhir
                    if (json.meta.current_page > json.meta.last_page) {
                        this.load(json.meta.last_page);
                        return;
                    }

                    this.items = json.data;
                    this.meta = json.meta;
                    this.page = json.meta.current_page;
                    this.syncUrl();
                } catch (e) {
                    if (seq === requestSeq) this.error = 'Gagal memuat data pengajuan. Silakan coba lagi.';
                } finally {
                    if (seq === requestSeq) this.loading = false;
                }
            },

            // Pencarian & filter baru selalu mulai dari halaman pertama; dilewati jika kriteria sama dengan yang terakhir diminta
            search() {
                if (!this.error && kriteria(this.filters) === kriteriaDiminta) return;
                return this.load(1);
            },

            resetSearch() {
                this.filters.q = '';
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
                if (this.filters.q.trim()) params.set('q', this.filters.q.trim());
                if (this.filters.status) params.set('status', this.filters.status);
                if (this.page > 1) params.set('page', this.page);
                history.replaceState(null, '', params.size ? `${location.pathname}?${params}` : location.pathname);
            },
        };
    }

    // Pesan singkat di pojok kanan bawah: success (hijau) / error (merah)
    function tampilkanPesanPengajuan(teks, jenis = 'success') {
        const el = document.createElement('div');
        el.setAttribute('role', jenis === 'success' ? 'status' : 'alert');
        el.className = 'p-4 rounded-xl border text-sm shadow-lg '
            + (jenis === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800');
        el.textContent = teks;
        document.getElementById('pesanPengajuan').appendChild(el);
        setTimeout(() => el.remove(), jenis === 'success' ? 3500 : 8000);
    }

    // Daftar pengajuan memuat ulang data saat event ini dikirim (setelah setujui / tolak / batalkan)
    const muatUlangPengajuan = () => window.dispatchEvent(new CustomEvent('pengajuan-diperbarui'));
</script>
