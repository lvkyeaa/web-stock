{{-- Lonceng notifikasi pengguna: peminjaman miliknya yang sudah disetujui / ditolak --}}
{{-- ─── 💡 REAL-TIME NOTIFIKASI STATUS UNTUK CUSTOMER ─── --}}
@php
    $notifCustomer = \App\Models\Peminjaman::with(['facility.facilityType', 'facilityRequest.facilityType'])->where('user_id', auth()->id())
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
         class="fixed inset-x-4 top-16 sm:absolute sm:inset-x-auto sm:top-auto sm:right-0 sm:mt-2 sm:w-80 bg-white rounded-2xl shadow-xl border border-gray-100 py-2 z-50 max-h-96 overflow-y-auto"
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
                    Pengajuan pinjam <span class="font-semibold text-bps-blue-dark">{{ $item->displayFacility()->facilityType->label() }}</span> (<b>{{ $item->displayFacility()->name }}</b>) Anda telah
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
