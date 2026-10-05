{{-- ─── 👤 MENU AKUN (admin & customer): klik avatar untuk membuka menu berisi tombol Keluar ─── --}}
<div class="relative pl-3 border-l border-gray-200 flex-shrink-0" x-data="{ open: false }" @keydown.escape.window="open = false">
    <button type="button" @click="open = !open" @click.outside="open = false"
        :aria-expanded="open.toString()" aria-haspopup="menu" aria-label="Menu akun"
        class="flex items-center gap-2 rounded-xl p-1 -m-1 hover:bg-slate-100 transition cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-bps-orange/40">
        <div class="w-8 h-8 rounded-xl bg-bps-orange flex items-center justify-center shadow-sm">
            <span class="text-white text-xs font-bold uppercase">{{ substr(auth()->user()->username, 0, 1) }}</span>
        </div>
        <div class="hidden sm:block text-left">
            <p class="text-xs font-bold text-gray-800">{{ auth()->user()->username }}</p>
            <p class="text-xs text-bps-orange font-semibold">{{ auth()->user()->labelPeran() }}</p>
        </div>
        <svg class="hidden sm:block w-4 h-4 text-gray-400 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    <div x-show="open" x-cloak role="menu"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute right-0 mt-2 w-56 origin-top-right bg-white rounded-2xl shadow-xl border border-gray-100 py-2 z-50">
        <div class="px-4 py-2 border-b border-gray-100">
            <p class="text-sm font-bold text-gray-800 truncate">{{ auth()->user()->name }}</p>
            <p class="text-xs text-gray-400 truncate">{{ auth()->user()->username }} · {{ auth()->user()->labelPeran() }}</p>
        </div>

        <form action="{{ route('logout') }}" method="POST" class="px-2 pt-2">
            @csrf
            <button type="submit" role="menuitem"
                class="w-full flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-semibold text-slate-600 hover:bg-red-500/10 hover:text-red-500 transition cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                Keluar
            </button>
        </form>
    </div>
</div>
