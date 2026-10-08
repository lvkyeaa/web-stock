{{-- Lonceng notifikasi admin: pengajuan persediaan & peminjaman fasilitas yang menunggu persetujuan --}}
{{-- ─── 💡 AKUMULASI NOTIFIKASI BARANG & FASILITAS PENDING ─── --}}
@php
    // 1. Ambil data pengajuan permintaan barang pending
    $pendingRequests = \App\Models\Order::with('user')
                        ->where('status', 'pending')
                        ->latest()
                        ->get();

    // 2. Ambil data pengajuan peminjaman fasilitas pending (Mobil/Ruang)
    $pendingFasilitas = \App\Models\Peminjaman::with(['user', 'facilityRequest.facilityType'])
                        ->where('status', 'pending')
                        ->latest()
                        ->get();

    // 3. Gabungkan total kuantitas hitungan untuk badge alarm
    $totalPendingCount = $pendingRequests->count() + $pendingFasilitas->count();
@endphp

<div class="relative" x-data="{ open: false }">
    <button @click="open = !open" @click.outside="open = false" class="relative p-2 rounded-xl text-gray-500 hover:bg-slate-100 transition cursor-pointer focus:outline-none">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>

        {{-- Bulatan Jingga Akumulasi Semua Pengajuan Pending --}}
        @if($totalPendingCount > 0)
            <span class="absolute top-1 right-1 flex h-4 w-4 items-center justify-center rounded-full bg-bps-orange text-[10px] font-bold text-white ring-2 ring-white animate-pulse">
                {{ $totalPendingCount }}
            </span>
        @endif
    </button>

    {{-- Isi Balon List Dropdown Notifikasi Masuk --}}
    <div x-show="open" 
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="transform opacity-0 scale-95"
         x-transition:enter-end="transform opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="transform opacity-100 scale-100"
         x-transition:leave-end="transform opacity-0 scale-95"
         class="fixed inset-x-4 top-16 sm:absolute sm:inset-x-auto sm:top-auto sm:right-0 sm:mt-2 sm:w-80 bg-white rounded-2xl shadow-xl border border-gray-100 py-2 z-50 max-h-96 overflow-y-auto"
         style="display: none;">

        <div class="px-4 py-2 font-bold text-xs text-gray-700 border-b border-gray-100 uppercase tracking-wider flex justify-between items-center">
            <span>Permintaan Masuk</span>
            @if($totalPendingCount > 0)
                <span class="text-[10px] bg-amber-50 text-bps-orange px-2 py-0.5 rounded-full font-bold">Baru</span>
            @endif
        </div>

        {{-- 📦 RENDER NOTIFIKASI PERMINTAAN PERSEDIAAN --}}
        @foreach($pendingRequests as $req)
            <a href="{{ route('pengajuan.index') }}" 
               class="block px-4 py-3 hover:bg-gray-50 text-xs text-gray-600 border-b border-gray-50 transition-all">
                <p class="font-semibold text-gray-800 leading-normal">
                    📦 Ada permintaan persediaan baru dari <span class="text-bps-orange font-bold">{{ $req->user->name ?? $req->user->username ?? 'User' }}</span>.
                </p>
                <span class="text-[10px] text-gray-400 mt-1 flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ $req->created_at->diffForHumans() }}
                </span>
            </a>
        @endforeach

        {{-- 🚗 / 🏢 RENDER NOTIFIKASI PEMINJAMAN FASILITAS --}}
        @foreach($pendingFasilitas as $pinjam)
            <a href="{{ route('peminjaman.index') }}" 
               class="block px-4 py-3 hover:bg-gray-50 text-xs text-gray-600 border-b border-gray-50 transition-all">
                <p class="font-semibold text-gray-800 leading-normal">
                    Pengajuan pinjam <span class="font-bold text-bps-blue-dark">{{ $pinjam->facilityRequest->facilityType->label() }}</span> baru dari <span class="text-bps-orange font-bold">{{ $pinjam->user->name ?? $pinjam->user->username ?? 'User' }}</span>.
                </p>
                <span class="text-[10px] text-gray-400 mt-1 flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ $pinjam->created_at->diffForHumans() }}
                </span>
            </a>
        @endforeach

        {{-- JIKA KEDUANYA KOSONG --}}
        @if($totalPendingCount === 0)
            <div class="px-4 py-8 text-center text-xs text-gray-400 space-y-2">
                <svg class="w-8 h-8 mx-auto text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0a2 2 0 01-2 2H6a2 2 0 01-2-2m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                <p>Belum ada pengajuan masuk</p>
            </div>
        @endif
    </div>
</div>
