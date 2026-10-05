<script>
    // Daftar barang (halaman kelola admin & katalog customer): dimuat lewat JSON tanpa reload halaman
    function barangList(config) {
        let requestSeq = 0; // respons lama yang datang terlambat diabaikan
        let kriteriaDiminta = null; // pencarian + filter dari permintaan terakhir (termasuk yang masih berjalan)
        const kriteria = (filters) => JSON.stringify([filters.search.trim(), filters.stokHabis, filters.urut]);

        return {
            items: [],
            meta: { current_page: 1, last_page: 1, from: null, to: null, total: 0 },
            filters: { search: config.search, stokHabis: config.stokHabis, urut: config.urut },
            page: config.page,
            loading: true,
            error: '',

            init() {
                this.load();
            },

            async load(page = this.page) {
                const seq = ++requestSeq;
                kriteriaDiminta = kriteria(this.filters);
                this.loading = true;
                this.error = '';

                const params = new URLSearchParams({ page, per_page: config.perPage });
                if (this.filters.search.trim()) params.set('search', this.filters.search.trim());
                if (this.filters.stokHabis) params.set('stok_habis', '1');
                params.set('urut', this.filters.urut);

                try {
                    const response = await fetch(`${config.dataUrl}?${params}`, { headers: { 'Accept': 'application/json' } });
                    if (!response.ok) throw new Error(response.status);
                    const json = await response.json();
                    if (seq !== requestSeq) return;

                    // Halaman di luar jangkauan (mis. dari URL lama setelah barang dihapus): pindah ke halaman terakhir
                    if (json.meta.current_page > json.meta.last_page) {
                        this.load(json.meta.last_page);
                        return;
                    }

                    this.items = json.data;
                    this.meta = json.meta;
                    this.page = json.meta.current_page;
                    this.syncUrl();
                } catch (e) {
                    if (seq === requestSeq) this.error = 'Gagal memuat data persediaan. Silakan coba lagi.';
                } finally {
                    if (seq === requestSeq) this.loading = false;
                }
            },

            // Pencarian & filter baru selalu mulai dari halaman pertama.
            // Dipanggil saat mengetik (debounce), Enter/Cari, switch stok habis, dan pilihan urutan; dilewati jika kriteria sama dengan yang tampil
            search() {
                if (!this.error && kriteria(this.filters) === kriteriaDiminta) return;
                return this.load(1);
            },

            resetSearch() {
                this.filters.search = '';
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

            // Simpan kondisi daftar di URL agar tetap sama setelah reload / kembali dari submit form
            syncUrl() {
                const params = new URLSearchParams();
                if (this.filters.search.trim()) params.set('search', this.filters.search.trim());
                if (this.filters.stokHabis) params.set('stok_habis', '1');
                if (this.filters.urut !== config.urutBawaan) params.set('urut', this.filters.urut);
                if (this.page > 1) params.set('page', this.page);
                history.replaceState(null, '', params.size ? `${location.pathname}?${params}` : location.pathname);
            },
        };
    }
</script>
