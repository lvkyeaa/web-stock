{{-- ─── 🛒 IKON KERANJANG (admin & customer): jumlah barang di keranjang, diperbarui lewat event keranjang-diperbarui ─── --}}
<a href="{{ route('keranjang.index') }}" title="Keranjang"
    x-data="{ jumlah: {{ auth()->user()->jumlahKeranjang() }} }" @keranjang-diperbarui.window="jumlah = $event.detail.jumlah"
    :aria-label="`Keranjang (${jumlah} item)`"
    class="relative p-2 rounded-xl transition {{ request()->routeIs('keranjang.*') ? 'bg-bps-orange/10 text-bps-orange' : 'text-gray-500 hover:bg-slate-100' }}">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
    </svg>
    <span x-show="jumlah > 0" x-cloak x-text="jumlah > 99 ? '99+' : jumlah"
        class="absolute -top-0.5 -right-0.5 flex h-4 min-w-4 px-1 items-center justify-center rounded-full bg-bps-orange text-[10px] font-bold text-white ring-2 ring-white"></span>
</a>
