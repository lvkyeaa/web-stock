{{-- Topbar untuk semua peran (dipakai layouts.admin & layouts.customer). Notifikasi dipilih per peran --}}
<header
    class="relative z-20 h-16 bg-white/90 backdrop-blur-xl border-b border-slate-200/70 flex items-center justify-between gap-3 px-4 sm:px-6 flex-shrink-0 shadow-sm">
    <div class="flex items-center gap-3 min-w-0">
        <button type="button" aria-label="Menu"
            @click="window.matchMedia('(min-width: 1024px)').matches ? sidebarCollapsed = !sidebarCollapsed : sidebarOpen = true"
            class="p-1.5 rounded-lg text-gray-500 hover:bg-gray-100 cursor-pointer flex-shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
        <div class="min-w-0">
            <h1 class="text-sm font-bold text-bps-blue-dark truncate">@yield('title', 'Dashboard')</h1>
            <p class="text-xs text-gray-400 hidden sm:block">Badan Pusat Statistik</p>
        </div>
    </div>

    <div class="flex items-center gap-3">

        @include('layouts.partials.ikon-keranjang')

        {{-- Notifikasi berbeda per peran --}}
        @role('admin')
            @include('layouts.partials.notifikasi-admin')
        @else
            @include('layouts.partials.notifikasi-customer')
        @endrole

        @include('layouts.partials.menu-akun')
    </div>
</header>
