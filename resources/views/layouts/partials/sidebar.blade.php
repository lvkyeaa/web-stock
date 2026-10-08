{{-- Sidebar untuk semua peran (dipakai layouts.admin & layouts.customer). Menu khusus admin dibatasi @role('admin') --}}
@php
    $isAdmin = auth()->user()->hasRole('admin');

    // Ikon menu (path SVG heroicons outline), satu definisi untuk semua peran
    $ikon = [
        'dashboard'  => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
        'katalog'    => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z',
        'pengajuan'  => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        'peminjaman' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
        'persediaan' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
        'persetujuan'=> 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
        'pengguna'   => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
    ];

    // Bagian menu: [judul (null = tanpa judul), daftar item]. aktif: pola route yang menandai menu terpilih.
    // Untuk customer, barang.index menampilkan katalog dan pengajuan.index menampilkan Pengajuan Saya, jadi ikut ditandai.
    $menu = [
        [null, [
            ['label' => 'Dashboard', 'ikon' => 'dashboard', 'url' => route($isAdmin ? 'admin.dashboard' : 'customer.dashboard'),
                'aktif' => [$isAdmin ? 'admin.dashboard' : 'customer.dashboard']],
        ]],
        ['Pengajuan Persediaan', [
            ['label' => 'Katalog Persediaan', 'ikon' => 'katalog', 'url' => route('barang.katalog'),
                'aktif' => $isAdmin ? ['barang.katalog'] : ['barang.katalog', 'barang.index']],
            ['label' => 'Pengajuan Saya', 'ikon' => 'pengajuan', 'url' => route('pengajuan.saya'),
                'aktif' => $isAdmin ? ['pengajuan.saya'] : ['pengajuan.saya', 'pengajuan.index']],
        ]],
        ['Fasilitas', [
            ['label' => 'Peminjaman Fasilitas', 'ikon' => 'peminjaman', 'url' => route('peminjaman.index'), 'aktif' => ['peminjaman.*']],
        ]],
    ];

    // Menu kelola khusus admin (route-nya juga dilindungi middleware role:admin)
    if ($isAdmin) {
        $menu[] = ['Kelola', [
            ['label' => 'Persediaan & Stok', 'ikon' => 'persediaan', 'url' => route('barang.index'), 'aktif' => ['barang.index']],
            ['label' => 'Persetujuan Pengajuan Persediaan', 'ikon' => 'persetujuan', 'url' => route('pengajuan.index'), 'aktif' => ['pengajuan.index']],
            ['label' => 'Pengguna', 'ikon' => 'pengguna', 'url' => route('admin.manajemen-user.index'), 'aktif' => ['admin.manajemen-user.*']],
        ]];
    }
@endphp

<aside id="sidebar" :class="{ 'is-open': sidebarOpen, 'is-collapsed': sidebarCollapsed }"
    class="w-64 bg-white/85 backdrop-blur-xl text-slate-700 flex flex-col flex-shrink-0 border-r border-slate-200/70 shadow-[0_20px_50px_-25px_rgba(15,23,42,0.2)]">
    {{-- Brand --}}
    <div class="px-6 py-5 border-b border-slate-200/70">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-white flex items-center justify-center flex-shrink-0 shadow-sm border border-slate-200/80">
                <svg class="w-6 h-6 text-bps-orange" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M3 3h4v8H3zm6-4h4v12H9zm6 2h4v10h-4zm-14 15h20v2H1z" />
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-[11px] font-bold text-bps-orange uppercase tracking-[0.01em] leading-tight">BPS Provinsi Jawa Timur</p>
                <p class="text-[10px] font-medium leading-tight text-slate-500 mt-0.5">Sistem Manajemen Persediaan</p>
            </div>
            {{-- Tutup sidebar (hanya di layar kecil) --}}
            <button type="button" @click="sidebarOpen = false" class="lg:hidden ml-auto p-1.5 rounded-lg text-slate-400 hover:bg-slate-100 cursor-pointer" aria-label="Tutup menu">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 px-4 py-6 space-y-6 overflow-y-auto">
        @foreach ($menu as [$judul, $items])
            <div class="space-y-1">
                @if ($judul)
                    <p class="px-3 mb-2 text-[11px] font-bold text-slate-400 uppercase tracking-widest">{{ $judul }}</p>
                @endif

                @foreach ($items as $item)
                    @php $aktif = request()->routeIs(...$item['aktif']); @endphp
                    <a href="{{ $item['url'] }}" @if ($aktif) aria-current="page" @endif
                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-150 {{ $aktif ? 'bg-bps-orange text-white shadow-sm border border-transparent' : 'text-slate-500 hover:bg-white hover:text-bps-orange' }}">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $ikon[$item['ikon']] }}" />
                        </svg>
                        <span class="text-sm font-semibold">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </div>
        @endforeach
    </nav>
</aside>
