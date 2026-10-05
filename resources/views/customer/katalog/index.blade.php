@extends(auth()->user()->hasRole('admin') ? 'layouts.admin' : 'layouts.customer')
@section('title', 'Katalog Persediaan')

@section('content')
    @include('barang.partials.list-script')

    <script>
        // Ringkas jumlah ala marketplace: 12 → "12", 257 → "250+", 1340 → "1rb+"
        function ringkasJumlah(n) {
            if (n < 100) return String(n);
            if (n < 1000) return `${Math.floor(n / 50) * 50}+`;
            return `${Math.floor(n / 1000)}rb+`;
        }
    </script>

    {{-- Daftar dimuat lewat JSON (barang.data): ganti halaman / filter tidak me-reload halaman --}}
    <div class="space-y-6" x-data="barangList(@js($initial))">
        {{-- HEADER BARIS UTAMA --}}
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-bps-blue/10 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 text-bps-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-semibold tracking-tight text-gray-900">Katalog Persediaan</h2>
                    <p class="text-sm text-gray-500">Pilih dan tambahkan persediaan ke keranjang pengajuanmu.</p>
                </div>
            </div>

            <a href="{{ route('keranjang.index') }}"
                x-data="{ jumlah: {{ auth()->user()->jumlahKeranjang() }} }" @keranjang-diperbarui.window="jumlah = $event.detail.jumlah"
                class="inline-flex items-center justify-center gap-2 bg-gradient-to-r from-bps-blue to-bps-blue-dark hover:from-bps-blue-dark hover:to-bps-blue text-white px-4 py-2.5 rounded-xl text-sm font-semibold transition shadow-[0_8px_24px_-12px_rgba(0,61,130,0.8)]">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                Lihat Keranjang
                <span x-show="jumlah > 0" x-text="jumlah > 99 ? '99+' : jumlah"
                    class="bg-white text-bps-blue-dark text-[11px] min-w-5 px-1.5 py-0.5 rounded-full font-bold ml-1 shadow-sm text-center"></span>
            </a>
        </div>

        {{-- FORM PENCARIAN --}}
        <div class="flex justify-end mb-6">
            <form action="{{ url()->current() }}" method="GET" @submit.prevent="search()" class="w-full md:w-auto flex flex-wrap items-center gap-2">
                <label class="order-last md:order-first w-full md:w-auto flex items-center gap-2 text-xs font-semibold text-slate-500 cursor-pointer select-none whitespace-nowrap">
                    <input type="checkbox" role="switch" name="stok_habis" value="1" x-model="filters.stokHabis" @change="search()" class="peer sr-only">
                    <span aria-hidden="true" class="relative inline-block w-9 h-5 shrink-0 rounded-full bg-slate-300 transition-colors peer-checked:bg-bps-blue peer-focus-visible:ring-2 peer-focus-visible:ring-bps-blue/40 after:absolute after:top-0.5 after:left-0.5 after:w-4 after:h-4 after:rounded-full after:bg-white after:shadow-sm after:transition-transform peer-checked:after:translate-x-4"></span>
                    Tampilkan stok habis
                </label>
                <div class="relative flex-1 md:flex-none md:w-80">
                    <input
                        type="text"
                        name="search"
                        x-model="filters.search" @input.debounce.400ms="search()"
                        placeholder="Cari nama persediaan..."
                        class="w-full pl-10 pr-10 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-700 shadow-sm focus:outline-none focus:ring-2 focus:ring-bps-blue/30 focus:border-bps-blue">

                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                <button type="submit" class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-bps-blue to-bps-blue-dark text-white text-sm font-semibold hover:from-bps-blue-dark hover:to-bps-blue transition cursor-pointer shadow-[0_8px_24px_-12px_rgba(0,61,130,0.8)]">
                    Cari
                </button>

                {{-- Urutan: terpopuler = paling sering diminta (pengajuan disetujui) dalam 90 hari terakhir --}}
                <label class="flex items-center gap-2 text-xs font-semibold text-slate-500 whitespace-nowrap">
                    Urutkan
                    <select x-model="filters.urut" @change="search()"
                        class="py-2.5 pl-3 pr-8 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-700 shadow-sm focus:outline-none focus:ring-2 focus:ring-bps-blue/30 focus:border-bps-blue cursor-pointer">
                        <option value="populer">Terpopuler (90 hari terakhir)</option>
                        <option value="terbaru">Terbaru</option>
                        <option value="nama">Nama A–Z</option>
                    </select>
                </label>
            </form>
        </div>

        {{-- GRID KATALOG BARANG --}}
        <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-5 transition-opacity" :class="loading && items.length && 'opacity-50'">
            <template x-for="item in items" :key="item.id">
                {{-- Tersedia = stok - yang sudah diajukan (pending). Tidak tersedia: kartu abu-abu & tidak bisa ditambahkan ke keranjang --}}
                <article class="group relative flex flex-col bg-white rounded-2xl border border-slate-200/70 overflow-hidden shadow-[0_1px_2px_rgba(15,23,42,0.04)] transition-all duration-300"
                    :class="item.tersedia > 0
                        ? 'hover:-translate-y-1 hover:border-bps-blue/20 hover:shadow-[0_18px_40px_-18px_rgba(0,61,130,0.35)]'
                        : 'opacity-70'">

                    {{-- 🖼️ FOTO BARANG --}}
                    <div class="relative h-28 sm:h-36 overflow-hidden bg-gradient-to-br from-slate-50 to-slate-100">
                        <img :src="item.foto_url" :alt="item.nama_barang" loading="lazy"
                            class="w-full h-full object-cover transition-transform duration-500 ease-out"
                            :class="item.tersedia > 0 ? 'group-hover:scale-[1.06]' : 'grayscale'">

                        <div x-show="item.tersedia <= 0" class="absolute inset-0 flex items-center justify-center bg-white/40">
                            <span class="rounded-full bg-slate-900/75 px-3 py-1 text-[11px] font-semibold uppercase tracking-wider text-white">Stok Habis</span>
                        </div>
                    </div>

                    {{-- INFO BARANG --}}
                    <div class="flex flex-1 flex-col p-3 sm:p-4">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400 font-mono truncate"
                            x-text="item.stock_id || 'Tanpa kode'"></p>
                        <h3 class="mt-1 text-sm font-semibold leading-snug tracking-tight text-slate-900 line-clamp-2 min-h-[2.5rem] sm:line-clamp-1 sm:min-h-0"
                            :title="item.nama_barang" x-text="item.nama_barang"></h3>
                        {{-- Jumlah pengajuan disetujui 90 hari terakhir (jendela disebut di pilihan urutan), gaya "250+ terjual".
                             Baris tetap ada agar tinggi kartu sama --}}
                        <p class="mt-1.5 h-5 flex items-center gap-1 text-xs text-slate-500 truncate"
                            :title="item.diminta_90_hari > 0 ? `Diminta ${item.diminta_90_hari} kali dalam 90 hari terakhir` : ''">
                            <template x-if="item.diminta_90_hari > 0">
                                <span class="inline-flex items-center gap-1">
                                    <svg class="w-4 h-4 shrink-0 text-bps-orange" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.214.33-.403.713-.57 1.116-.334.804-.614 1.768-.84 2.734a31.365 31.365 0 00-.613 3.58 2.64 2.64 0 01-.945-1.067c-.328-.68-.398-1.534-.398-2.654A1 1 0 005.05 6.05 6.981 6.981 0 003 11a7 7 0 1011.95-4.95c-.592-.591-.98-.985-1.348-1.467-.363-.476-.724-1.063-1.207-2.03zM12.12 15.12A3 3 0 017 13s.879.5 2.5.5c0-1 .5-4 1.25-4.5.5 1 .786 1.293 1.371 1.879A2.99 2.99 0 0113 13a2.99 2.99 0 01-.879 2.121z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="font-bold text-slate-800" x-text="ringkasJumlah(item.diminta_90_hari) + '×'"></span>
                                    <span class="font-medium text-slate-700">diminta</span>
                                </span>
                            </template>
                        </p>

                        {{-- STOK & TOMBOL TAMBAH --}}
                        <div class="mt-3 sm:mt-4 pt-3 flex flex-col items-stretch gap-2 sm:flex-row sm:items-end sm:justify-between sm:gap-3 border-t border-slate-100">
                            <div class="min-w-0">
                                <p class="text-[10px] font-medium uppercase tracking-wider text-slate-400">Tersedia</p>
                                <p class="text-sm text-slate-900 truncate">
                                    <span class="text-lg font-bold tabular-nums" x-text="item.tersedia"></span>
                                    <span class="text-xs font-medium text-slate-500" x-text="item.satuan"></span>
                                </p>
                            </div>

                            <button x-show="item.tersedia > 0" type="button"
                                @click="openAjukanModal(item.id, item.nama_barang, item.tersedia, item.satuan, item.foto_url)"
                                :aria-label="`Tambah ${item.nama_barang} ke keranjang`"
                                class="inline-flex shrink-0 items-center justify-center gap-1.5 rounded-xl bg-bps-blue px-3.5 py-2 text-xs font-semibold text-white shadow-sm shadow-bps-blue/30 transition hover:bg-bps-blue-dark active:scale-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-bps-blue/40 focus-visible:ring-offset-2 cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Tambah
                            </button>
                            <button x-show="item.tersedia <= 0" type="button" disabled
                                class="inline-flex shrink-0 items-center justify-center rounded-xl bg-slate-100 px-3.5 py-2 text-xs font-semibold text-slate-400 cursor-not-allowed">
                                Stok Habis
                            </button>
                        </div>
                    </div>
                </article>
            </template>

            <div x-show="loading && !items.length" class="col-span-full text-center py-12 text-gray-400">Memuat data persediaan...</div>
            <div x-show="!loading && !error && !items.length" x-cloak class="col-span-full text-center py-12 text-gray-400">Belum ada persediaan tersedia.</div>
            <div x-show="error" x-cloak class="col-span-full text-center py-12 text-sm text-red-600">
                <span x-text="error"></span>
                <button type="button" @click="load()" class="ml-2 font-semibold underline cursor-pointer">Coba lagi</button>
            </div>
        </div>

        @include('barang.partials.pagination')
    </div>

    @include('barang.partials.tambah-keranjang')
@endsection