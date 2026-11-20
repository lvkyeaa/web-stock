{{-- ─── 🔔 LONCENG NOTIFIKASI (admin & customer) dari tabel notifications: pengajuan persediaan & peminjaman fasilitas.
     Status dibaca tersimpan di database (read_at), jadi sama di semua perangkat. Klik notifikasi → ditandai dibaca lalu ke tautannya. ─── --}}
@php
    $notifikasi = auth()->user()->notifications()->latest()->take(10)->get();
    $jumlahBelumDibaca = auth()->user()->unreadNotifications()->count();
@endphp

<div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false">
    <button type="button" @click="open = !open" @click.outside="open = false" :aria-expanded="open.toString()"
        aria-label="Notifikasi{{ $jumlahBelumDibaca ? " ($jumlahBelumDibaca belum dibaca)" : '' }}"
        class="relative p-2 rounded-xl text-gray-500 hover:bg-slate-100 transition cursor-pointer focus:outline-none">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>
        @if ($jumlahBelumDibaca)
            <span class="absolute top-1 right-1 flex h-4 min-w-4 px-1 items-center justify-center rounded-full bg-bps-orange text-[10px] font-bold text-white ring-2 ring-white">
                {{ $jumlahBelumDibaca > 9 ? '9+' : $jumlahBelumDibaca }}
            </span>
        @endif
    </button>

    {{-- Ponsel: selebar layar di bawah top bar; sm ke atas: balon di kanan tombol --}}
    <div x-show="open" x-cloak
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="fixed inset-x-4 top-16 sm:absolute sm:inset-x-auto sm:top-auto sm:right-0 sm:mt-2 sm:w-96 bg-white rounded-2xl shadow-xl border border-gray-100 z-50 max-h-[70dvh] overflow-y-auto">

        <div class="sticky top-0 bg-white px-4 py-3 border-b border-gray-100 flex items-center justify-between gap-3">
            <span class="font-bold text-xs text-gray-700 uppercase tracking-wider">Notifikasi</span>
            @if ($jumlahBelumDibaca)
                <form method="POST" action="{{ route('notifikasi.baca-semua') }}">
                    @csrf
                    <button type="submit" class="text-xs font-semibold text-bps-blue hover:underline cursor-pointer">Tandai semua dibaca</button>
                </form>
            @endif
        </div>

        @forelse ($notifikasi as $item)
            <a href="{{ route('notifikasi.buka', $item->id) }}"
                class="flex gap-3 px-4 py-3 border-b border-gray-50 hover:bg-gray-50 transition {{ $item->read_at ? '' : 'bg-bps-blue/5' }}">
                <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full {{ $item->read_at ? 'bg-transparent' : 'bg-bps-orange' }}" aria-hidden="true"></span>
                <span class="min-w-0">
                    <span class="block text-xs leading-normal whitespace-pre-line break-words {{ $item->read_at ? 'text-gray-600' : 'text-gray-900 font-semibold' }}">{{ $item->data['pesan'] ?? '-' }}</span>
                    <span class="block text-[10px] text-gray-400 mt-1">
                        {{ $item->created_at->diffForHumans() }}
                        @unless ($item->read_at)<span class="sr-only">(belum dibaca)</span>@endunless
                    </span>
                </span>
            </a>
        @empty
            <p class="px-4 py-8 text-center text-xs text-gray-400">Belum ada notifikasi</p>
        @endforelse
    </div>
</div>
