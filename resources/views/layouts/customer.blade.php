<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') — BPS Customer</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="bg-bps-cream-bg font-[Plus_Jakarta_Sans] flex h-screen overflow-hidden">

    {{-- SIDEBAR --}}
    <aside id="sidebar" class="w-64 bg-white/85 backdrop-blur-xl text-slate-700 flex flex-col flex-shrink-0 border-r border-slate-200/70 shadow-[0_20px_50px_-25px_rgba(15,23,42,0.2)]">
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
            </div>
        </div>

        {{-- Role Badge --}}
        <div class="px-6 py-3 bg-white/70 border-b border-slate-200/70">
            <div class="flex items-center gap-2">
                <div class="w-2 h-2 rounded-full bg-bps-orange animate-pulse"></div>
                <span class="text-xs font-semibold text-bps-blue-dark uppercase tracking-wider">Customer</span>
            </div>
        </div>

        <nav class="flex-1 px-4 py-6 space-y-1 overflow-y-auto">
            <p class="text-xs font-bold text-slate-500 uppercase tracking-widest px-3 mb-3">Menu Utama</p>

            <a href="{{ route('customer.dashboard') }}"
                class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-150 {{ request()->routeIs('customer.dashboard') ? 'bg-bps-orange text-white shadow-sm border border-transparent' : 'text-slate-500 hover:bg-white hover:text-bps-orange' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                <span class="text-sm font-semibold">Dashboard</span>
            </a>

            <a href="{{ route('customer.katalog.index') }}"
                class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-150 {{ request()->routeIs('customer.katalog.*') ? 'bg-bps-orange text-white shadow-sm border border-transparent' : 'text-slate-500 hover:bg-white hover:text-bps-orange' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                </svg>
                <span class="text-sm font-semibold">Katalog Barang</span>
            </a>

            <a href="{{ route('customer.pengajuan.index') }}"
                class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-150 {{ request()->routeIs('customer.pengajuan.*') ? 'bg-bps-orange text-white shadow-sm border border-transparent' : 'text-slate-500 hover:bg-white hover:text-bps-orange' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span class="text-sm font-semibold">Pengajuan Saya</span>
            </a>

            {{-- MENU CUSTOMER: Peminjaman Fasilitas --}}
            <a href="{{ route('customer.peminjaman.index') }}"
                class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-150 {{ request()->routeIs('customer.peminjaman.*') ? 'bg-bps-orange text-white shadow-sm border border-transparent' : 'text-slate-500 hover:bg-white hover:text-bps-orange' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span class="text-sm font-semibold">Peminjaman Fasilitas</span>
            </a>
        </nav>

        <div class="p-4 border-t border-slate-200/70">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit"
                    class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-500 hover:bg-red-500/10 hover:text-red-500 transition-all cursor-pointer text-sm font-semibold">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    Keluar
                </button>
            </form>
        </div>
    </aside>

    <div class="flex-1 flex flex-col overflow-hidden">
        {{-- NAVBAR --}}
        <header
            class="h-16 bg-white/90 backdrop-blur-xl border-b border-slate-200/70 flex items-center justify-between px-6 flex-shrink-0 shadow-sm">
            <div>
                <h1 class="text-sm font-bold text-bps-blue-dark">@yield('title', 'Dashboard')</h1>
                <p class="text-xs text-gray-400">Badan Pusat Statistik</p>
            </div>

            <div class="flex items-center gap-3">
                
                {{-- ─── 💡 REAL-TIME NOTIFIKASI STATUS UNTUK CUSTOMER ─── --}}
                @php
                    $notifCustomer = \App\Models\Peminjaman::where('user_id', session('auth_user.id'))
                                        ->whereIn('status', ['disetujui', 'ditolak'])
                                        ->latest()
                                        ->take(5)
                                        ->get();
                    
                    // Kumpulkan semua ID notifikasi mentah untuk divalidasi ke AlpineJS
                    $allNotifIds = $notifCustomer->pluck('id')->toArray();
                @endphp

                <div class="relative" 
                     x-data="{ 
                        open: false,
                        readIds: JSON.parse(localStorage.getItem('read_peminjaman_ids') || '[]'),
                        allIds: {{ json_encode($allNotifIds) }},
                        
                        // Menghitung apakah masih ada item yang belum dibaca dari database
                        get hasUnread() {
                            return this.allIds.some(id => !this.readIds.includes(id));
                        },
                        markAsRead(id) {
                            if (!this.readIds.includes(id)) {
                                this.readIds.push(id);
                                localStorage.setItem('read_peminjaman_ids', JSON.stringify(this.readIds));
                            }
                        }
                     }">
                     
                    <button @click="open = !open" @click.outside="open = false" class="relative p-2 rounded-xl text-gray-500 hover:bg-slate-100 transition cursor-pointer focus:outline-none">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        
                        {{-- Badge penanda ada update status: Otomatis mati/hilang jika hasUnread bernilai false --}}
                        <span x-show="hasUnread" class="absolute top-1 right-1 flex h-2 w-2 rounded-full bg-emerald-500 ring-2 ring-white" style="display: none;"></span>
                    </button>

                    {{-- Dropdown Balon Notifikasi Customer --}}
                    <div x-show="open" 
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="transform opacity-0 scale-95"
                         x-transition:enter-end="transform opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="transform opacity-100 scale-100"
                         x-transition:leave-end="transform opacity-0 scale-95"
                         class="absolute right-0 mt-2 w-80 bg-white rounded-2xl shadow-xl border border-gray-100 py-2 z-50 max-h-96 overflow-y-auto"
                         style="display: none;">
                        
                        <div class="px-4 py-2 font-bold text-xs text-gray-700 border-b border-gray-100 uppercase tracking-wider flex justify-between items-center">
                            <span>Notifikasi Peminjaman</span>
                        </div>
                        
                        @forelse($notifCustomer as $item)
                            <div x-show="!readIds.includes({{ $item->id }})" 
                                 class="block px-4 py-3 border-b border-gray-50 text-xs text-gray-600 transition-all relative group pr-8">
                                
                                {{-- Tombol Silang (X) untuk Menghapus Notifikasi secara Lokal --}}
                                <button @click="markAsRead({{ $item->id }})" 
                                        class="absolute top-3 right-3 text-gray-400 hover:text-rose-500 cursor-pointer text-[10px] font-bold p-1 transition-colors">
                                    ✕
                                </button>

                                <p class="leading-normal text-gray-800">
                                    Pengajuan pinjam <span class="font-semibold capitalize text-bps-blue-dark">{{ $item->jenis_fasilitas }}</span> (<b>{{ $item->nama_item }}</b>) Anda telah 
                                    @if($item->status === 'disetujui')
                                        <span class="text-emerald-600 font-bold bg-emerald-50 px-1.5 py-0.5 rounded text-[10px]">DISETUJUI</span>
                                    @else
                                        <span class="text-rose-600 font-bold bg-rose-50 px-1.5 py-0.5 rounded text-[10px]">DITOLAK</span>
                                    @endif
                                </p>
                                <span class="text-[10px] text-gray-400 mt-1.5 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    {{ $item->updated_at->diffForHumans() }}
                                </span>
                            </div>
                        @empty
                        @endforelse

                        {{-- Tampilan saat semua notifikasi bawaan kosong atau telah di-klik silang seluruhnya --}}
                        <div x-show="!hasUnread" class="px-4 py-8 text-center text-xs text-gray-400">
                            <p>Belum ada pembaruan status</p>
                        </div>
                    </div>
                </div>

                {{-- Profile --}}
                <div class="flex items-center gap-2 pl-3 border-l border-gray-200">
                    <div class="w-8 h-8 rounded-xl bg-bps-orange flex items-center justify-center shadow-sm">
                        <span
                            class="text-white text-xs font-bold uppercase">{{ substr(session('auth_user.username', 'C'), 0, 1) }}</span>
                    </div>
                    <div class="hidden sm:block">
                        <p class="text-xs font-bold text-gray-800">{{ session('auth_user.username') }}</p>
                        <p class="text-xs text-bps-orange font-semibold capitalize">{{ session('auth_user.role') }}</p>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-6">
            @if (session('success'))
                <div
                    class="mb-4 p-4 rounded-xl bg-green-50 border border-green-200 text-green-800 text-sm flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd" />
                    </svg>
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div
                    class="mb-4 p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm flex items-center gap-2">
                    <svg class="w-5 h-5 text-red-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd" />
                    </svg>
                    {{ session('error') }}
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</body>

</html>