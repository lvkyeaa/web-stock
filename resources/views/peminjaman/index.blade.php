@extends($isGuest ? 'layouts.public' : ($isAdmin ? 'layouts.admin' : 'layouts.customer'))

@section('title', $isGuest ? 'Jadwal Fasilitas' : ($isAdmin ? 'Manajemen Persetujuan Peminjaman' : 'Peminjaman Fasilitas'))

@section('content')
@php
    // Ikon status: centang = disetujui, jam = menunggu, silang = ditolak
    $statusIcons = [
        'disetujui' => 'M5 13l4 4L19 7',
        'pending'   => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
        'ditolak'   => 'M6 18L18 6M6 6l12 12',
    ];
@endphp
<div class="space-y-6" x-data="bookingPage(@js($initial + ['dataUrl' => route('peminjaman.data'), 'csrf' => csrf_token(), 'facilities' => $facilities]))"
    @booking-saved.window="bookingSaved($event.detail.message)">

    {{-- ─── CARD UTAMA KALENDER & DAFTAR PEMINJAMAN ─── --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4 sm:p-6">

        {{-- CARD HEADER: JUDUL + TOMBOL BUAT PEMINJAMAN --}}
        <div class="mb-6 border-b border-gray-100 pb-5">
            <div class="flex flex-wrap items-center justify-between gap-3">

                {{-- 1. JUDUL DAN SUBJUDUL --}}
                <div>
                    <h2 class="text-xl font-bold tracking-tight text-bps-blue-dark">Jadwal Fasilitas</h2>
                    <p class="text-xs text-gray-400">Pantau jadwal peminjaman mobil dinas dan ruang rapat</p>
                </div>

                {{-- 2. TOMBOL BUAT PEMINJAMAN (tamu: tautan masuk) --}}
                @if($isGuest)
                    <a href="{{ route('login') }}"
                        class="flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold bg-gradient-to-r from-bps-blue to-bps-blue-dark hover:from-bps-blue-dark hover:to-bps-blue text-white transition shadow-[0_8px_24px_-12px_rgba(0,61,130,0.8)]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                        </svg>
                        Masuk untuk meminjam
                    </a>
                @else
                <button type="button" @click="$dispatch('open-booking-modal')"
                    class="flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold bg-gradient-to-r from-bps-blue to-bps-blue-dark hover:from-bps-blue-dark hover:to-bps-blue text-white transition shadow-[0_8px_24px_-12px_rgba(0,61,130,0.8)] cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Buat Peminjaman
                </button>
                @endif
            </div>
        </div>

        {{-- ─── PILIHAN TAMPILAN KALENDER / DAFTAR (BAGIAN DARI KONTEN) ─── --}}
        <div class="flex gap-1.5 bg-orange-50 p-1.5 rounded-2xl w-full sm:w-fit border border-orange-200 mb-5">
            <button type="button" @click="showTab('calendar')"
                :class="tab === 'calendar' ? 'bg-bps-orange text-white shadow-md shadow-orange-200' : 'text-bps-orange hover:bg-white'"
                class="flex-1 sm:flex-none flex items-center justify-center gap-2 px-4 sm:px-5 py-2.5 rounded-xl text-sm font-bold transition-all cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                Kalender
            </button>
            <button type="button" @click="showTab('list')"
                :class="tab === 'list' ? 'bg-bps-orange text-white shadow-md shadow-orange-200' : 'text-bps-orange hover:bg-white'"
                class="flex-1 sm:flex-none flex items-center justify-center gap-2 px-4 sm:px-5 py-2.5 rounded-xl text-sm font-bold transition-all cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                </svg>
                Tabel
            </button>
        </div>

        {{-- ─── FILTER (BERLAKU UNTUK KALENDER & DAFTAR, TANPA RELOAD HALAMAN) ─── --}}
        <div class="flex flex-wrap items-end justify-between gap-3 mb-4">
            <div class="grid grid-cols-2 sm:flex sm:flex-wrap items-end gap-3 w-full sm:w-auto">
                {{-- Pencarian (dikirim ke server setelah berhenti mengetik) --}}
                <div class="col-span-2 sm:w-64">
                    <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Cari</label>
                    <div class="relative">
                        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z" /></svg>
                        <input type="search" x-model="filters.q" @input.debounce.400ms="load()" maxlength="100"
                            placeholder="Keperluan, pemohon, fasilitas…"
                            class="w-full pl-9 pr-3 py-2 bg-white border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:border-bps-blue focus:ring-4 focus:ring-bps-blue/10">
                    </div>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Jenis Fasilitas</label>
                    <select x-model="filters.type" @change="load()"
                        class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:border-bps-blue focus:ring-4 focus:ring-bps-blue/10 cursor-pointer">
                        <option value="">Semua Fasilitas</option>
                        @foreach($types as $facilityType)
                            <option value="{{ $facilityType->code }}">{{ $facilityType->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Status</label>
                    <select x-model="filters.status" @change="load()"
                        class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:border-bps-blue focus:ring-4 focus:ring-bps-blue/10 cursor-pointer">
                        <option value="">Semua Status</option>
                        <option value="pending">Menunggu</option>
                        <option value="disetujui">Disetujui</option>
                        <option value="ditolak">Ditolak</option>
                    </select>
                </div>
                @unless($isGuest)
                    {{-- Peminjaman saya --}}
                    <label class="col-span-2 sm:col-auto flex items-center gap-2 px-3 py-2 rounded-xl border text-xs font-semibold cursor-pointer select-none transition"
                        :class="filters.mine ? 'bg-bps-blue-dark border-bps-blue-dark text-white' : 'bg-white border-slate-200 text-slate-600 hover:border-slate-300'">
                        <input type="checkbox" x-model="filters.mine" @change="load()" class="sr-only">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                        Peminjaman saya
                    </label>
                @endunless
                <button type="button" x-show="filters.type || filters.status || filters.q || filters.mine" x-cloak @click="resetFilters()"
                    class="col-span-2 justify-self-start px-3 py-2 text-xs font-semibold text-gray-500 hover:text-gray-800 cursor-pointer">Reset</button>
            </div>
        </div>

        {{-- ─── TOOLBAR PERIODE (BERSAMA UNTUK KALENDER & DAFTAR) ─── --}}
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <div class="flex items-center gap-1.5">
                <button type="button" @click="prev()" title="Sebelumnya" class="period-btn">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" /></svg>
                </button>
                <button type="button" @click="next()" title="Berikutnya" class="period-btn">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" /></svg>
                </button>
                <button type="button" @click="today()" class="period-btn px-3">Hari Ini</button>
            </div>

            <h3 class="order-first sm:order-none w-full sm:w-auto text-base font-bold text-bps-blue-dark capitalize flex items-center justify-center sm:justify-start gap-2">
                <span x-text="title"></span>
                <svg x-show="loading" x-cloak class="w-4 h-4 animate-spin text-slate-400" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"/><path fill="currentColor" class="opacity-75" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
            </h3>

            <div class="flex bg-slate-100 p-1 rounded-xl border border-slate-200">
                <button type="button" @click="setMode('month')" :class="mode === 'month' ? 'bg-white shadow-sm text-bps-blue-dark' : 'text-slate-500 hover:text-slate-800'"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer">Bulan</button>
                <button type="button" @click="setMode('week')" :class="mode === 'week' ? 'bg-white shadow-sm text-bps-blue-dark' : 'text-slate-500 hover:text-slate-800'"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer">Minggu</button>
            </div>
        </div>

        <div x-show="error" x-cloak x-text="error" class="mb-4 bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-xl text-sm font-medium"></div>
        <div x-show="notice" x-cloak x-text="notice" class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm font-medium"></div>

        {{-- ─── LEGENDA: menempel di atas kalender (dan di atas daftar); warna = jenis, gaya = status ─── --}}
        <div class="flex flex-wrap items-center gap-x-4 gap-y-2 px-3 py-2 bg-slate-50 border border-slate-200 text-[11px] font-medium text-gray-500"
            :class="tab === 'calendar' ? 'rounded-t-xl border-b-0' : 'rounded-xl mb-3'">
            @foreach($types as $facilityType)
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-white border-2" style="border-color:{{ $facilityType->color }}"></span>{{ $facilityType->name }}</span>
            @endforeach
            <span class="w-px h-4 bg-gray-200"></span>
            <span class="booking-badge booking-status-disetujui" style="background:#ffffff;border-color:#475569;color:#475569"><svg class="booking-status-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $statusIcons['disetujui'] }}"/></svg>Disetujui</span>
            <span class="booking-badge booking-status-pending" style="background:#ffffff;border-color:#475569;color:#475569"><svg class="booking-status-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $statusIcons['pending'] }}"/></svg>Menunggu</span>
            <span class="booking-badge booking-status-ditolak" style="background:#ffffff;border-color:#cbd5e1;color:#94a3b8"><svg class="booking-status-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $statusIcons['ditolak'] }}"/></svg>Ditolak</span>
        </div>

        {{-- ─── TAB 1: KALENDER ─── --}}
        <div x-show="tab === 'calendar'">
            <div class="overflow-x-auto -mx-4 px-4 sm:mx-0 sm:px-0">
                <div x-ref="calendar" class="min-h-[450px] min-w-[640px]"></div>
            </div>
        </div>

        {{-- ─── TAB 2: DAFTAR / TABEL ─── --}}
        <div x-show="tab === 'list'" x-cloak>
            {{-- Mobile: kartu --}}
            <div class="md:hidden space-y-3">
                <div class="flex items-center justify-end gap-2">
                    <label class="text-[11px] font-bold text-slate-500 uppercase">Urutkan</label>
                    <select @change="setSort($event.target.value)"
                        class="px-3 py-1.5 bg-white border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:border-bps-blue cursor-pointer">
                        <template x-for="opt in sortOptions" :key="opt.value">
                            <option :value="opt.value" x-text="opt.label" :selected="opt.value === sort.key + ':' + sort.dir"></option>
                        </template>
                    </select>
                </div>
                <template x-for="b in listBookings" :key="b.id">
                    <div class="rounded-xl border border-gray-200 p-3 space-y-2" :style="'border-left: 4px solid ' + b.type.color">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-800 truncate" x-text="b.type.icon + ' ' + b.facility"></p>
                                <p class="text-xs text-amber-600" x-show="b.assigned_facility && b.assigned_facility.id !== b.requested_facility.id"
                                    x-text="'Diminta: ' + b.requested_facility.name"></p>
                                <p class="text-xs text-gray-500" x-text="b.user"></p>
                            </div>
                            <div class="flex-shrink-0 whitespace-nowrap">
                            <span :class="'booking-badge booking-status-' + b.status"
                                :style="`background:${b.style.bg};border-color:${b.style.border};color:${b.style.text}`">
                                <span x-html="statusIcon(b.status)"></span><span x-text="statusLabel[b.status]"></span>
                            </span>
                            </div>
                        </div>
                        <div class="text-xs space-y-0.5">
                            <div class="text-green-600" x-text="'Mulai: ' + b.start_label"></div>
                            <div class="text-red-600" x-text="'Selesai: ' + b.end_label"></div>
                        </div>
                        <p class="text-xs text-gray-600 break-words" x-text="b.keperluan || '-'"></p>
                        @unless($isGuest)
                        <div class="flex gap-2 pt-1" x-show="{{ $isAdmin ? 'true' : 'b.can_edit || b.can_delete' }}">
                            <button type="button" x-show="b.can_edit" @click="editBooking(b)"
                                class="flex-1 py-2 flex items-center justify-center gap-1.5 text-bps-blue-dark bg-white border border-slate-300 hover:bg-slate-50 text-xs font-semibold rounded-xl shadow-sm cursor-pointer">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536M9 13l6.232-6.232a2.5 2.5 0 113.536 3.536L12.536 16.536 8 18l1.464-4.536z" /></svg>
                                Edit
                            </button>
                            <button type="button" x-show="b.can_delete" @click="deleteBooking(b)" :disabled="acting" title="Hapus"
                                class="px-3 py-2 flex items-center justify-center gap-1.5 text-rose-600 bg-white border border-rose-200 hover:bg-rose-50 text-xs font-semibold rounded-xl shadow-sm cursor-pointer disabled:opacity-50">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M4 7h16M10 3h4a1 1 0 011 1v3H9V4a1 1 0 011-1z" /></svg>
                                Hapus
                            </button>
                            @if($isAdmin)
                                <button type="button" @click="openDetail(b)"
                                    class="flex-1 py-2 flex items-center justify-center gap-1.5 bg-gradient-to-r from-bps-blue to-bps-blue-dark text-white text-xs font-semibold rounded-xl shadow-sm cursor-pointer">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    Persetujuan
                                </button>
                            @endif
                        </div>
                        @endunless
                    </div>
                </template>
                <p x-show="!loading && listBookings.length === 0" class="text-center py-12 text-sm text-gray-400">Tidak ada peminjaman fasilitas pada periode ini.</p>
            </div>

            {{-- Tablet & desktop: tabel --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-gray-200 text-xs font-bold text-gray-500 uppercase bg-gray-50">
                            <th class="p-3">
                                <button type="button" @click="sortBy('user')" class="inline-flex items-center gap-1 uppercase font-bold cursor-pointer hover:text-gray-800"
                                    :class="sort.key === 'user' && 'text-bps-blue-dark'">
                                    Nama Pemohon <span class="text-[10px]" x-text="sortIcon('user')"></span>
                                </button>
                            </th>
                            <th class="p-3">
                                <button type="button" @click="sortBy('facility')" class="inline-flex items-center gap-1 uppercase font-bold cursor-pointer hover:text-gray-800"
                                    :class="sort.key === 'facility' && 'text-bps-blue-dark'">
                                    Fasilitas / Item <span class="text-[10px]" x-text="sortIcon('facility')"></span>
                                </button>
                            </th>
                            <th class="p-3">
                                <button type="button" @click="sortBy('start')" class="inline-flex items-center gap-1 uppercase font-bold cursor-pointer hover:text-gray-800"
                                    :class="sort.key === 'start' && 'text-bps-blue-dark'">
                                    Waktu Pinjam <span class="text-[10px]" x-text="sortIcon('start')"></span>
                                </button>
                            </th>
                            <th class="p-3">Keperluan</th>
                            <th class="p-3">
                                <button type="button" @click="sortBy('status')" class="inline-flex items-center gap-1 uppercase font-bold cursor-pointer hover:text-gray-800"
                                    :class="sort.key === 'status' && 'text-bps-blue-dark'">
                                    Status <span class="text-[10px]" x-text="sortIcon('status')"></span>
                                </button>
                            </th>
                            @unless($isGuest)
                                <th class="p-3 text-center">Aksi</th>
                            @endunless
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm">
                        <template x-for="b in listBookings" :key="b.id">
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="p-3 font-medium text-gray-900" x-text="b.user"></td>
                                <td class="p-3 font-semibold text-gray-700">
                                    <span class="block" x-text="b.facility"></span>
                                    <span class="block text-xs font-normal text-amber-600" x-show="b.assigned_facility && b.assigned_facility.id !== b.requested_facility.id"
                                        x-text="'Diminta: ' + b.requested_facility.name"></span>
                                    <span class="text-xs font-normal text-gray-400">
                                        Jenis: <b :style="'color:' + b.type.color" x-text="b.type.label"></b>
                                    </span>
                                </td>
                                <td class="p-3 text-xs text-gray-600 space-y-0.5">
                                    <div class="text-green-600" x-text="'Mulai: ' + b.start_label"></div>
                                    <div class="text-red-600" x-text="'Selesai: ' + b.end_label"></div>
                                </td>
                                <td class="p-3 text-gray-600 max-w-xs truncate" x-text="b.keperluan || '-'"></td>
                                <td class="p-3">
                                    <span :class="'booking-badge booking-status-' + b.status"
                                        :style="`background:${b.style.bg};border-color:${b.style.border};color:${b.style.text}`">
                                        <span x-html="statusIcon(b.status)"></span><span x-text="statusLabel[b.status]"></span>
                                    </span>
                                </td>
                                @unless($isGuest)
                                <td class="p-3">
                                    {{-- Tombol ikon ringkas; label tampil sebagai tooltip --}}
                                    <div class="flex items-center justify-center gap-1.5">
                                    @if($isAdmin)
                                        <button type="button" @click="openDetail(b)" aria-label="Persetujuan" x-init="tip($el, 'Persetujuan')"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg shadow-sm transition cursor-pointer bg-gradient-to-r from-bps-blue to-bps-blue-dark hover:from-bps-blue-dark hover:to-bps-blue text-white">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        </button>
                                    @endif
                                        <button type="button" x-show="b.can_edit" @click="editBooking(b)" aria-label="Edit" x-init="tip($el, 'Edit')"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg shadow-sm transition cursor-pointer text-bps-blue-dark bg-white border border-slate-300 hover:bg-slate-50">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536M9 13l6.232-6.232a2.5 2.5 0 113.536 3.536L12.536 16.536 8 18l1.464-4.536z" /></svg>
                                        </button>
                                        <button type="button" x-show="b.can_delete" @click="deleteBooking(b)" :disabled="acting" aria-label="Hapus" x-init="tip($el, 'Hapus')"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg shadow-sm transition cursor-pointer text-rose-600 bg-white border border-rose-200 hover:bg-rose-50 disabled:opacity-50">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M4 7h16M10 3h4a1 1 0 011 1v3H9V4a1 1 0 011-1z" /></svg>
                                        </button>
                                    @unless($isAdmin)
                                        <span x-show="!b.can_edit && !b.can_delete" class="text-xs text-gray-400">-</span>
                                    @endunless
                                    </div>
                                </td>
                                @endunless
                            </tr>
                        </template>
                        <tr x-show="!loading && listBookings.length === 0">
                            <td colspan="{{ $isGuest ? 5 : 6 }}" class="text-center py-12 text-gray-400">Tidak ada peminjaman fasilitas pada periode ini.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ─── DIALOG DETAIL PEMINJAMAN (KLIK ITEM KALENDER) ─── --}}
    <div x-show="selected" x-cloak
        @keydown.escape.window="if (!confirmBox.open) selected = null"
        @click.self="selected = null"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4">
        <template x-if="selected">
            <div class="bg-white rounded-2xl shadow-xl border border-gray-200 w-full max-w-md max-h-[90dvh] overflow-y-auto">
                <div class="p-5 sm:p-6 space-y-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs font-bold uppercase" :style="'color:' + selected.type.color" x-text="selected.type.label"></p>
                            <h2 class="text-lg font-bold text-bps-blue-dark break-words" x-text="selected.facility"></h2>
                        </div>
                        <button type="button" @click="selected = null" class="text-gray-400 hover:text-gray-700 cursor-pointer flex-shrink-0" aria-label="Tutup">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <span :class="'booking-badge booking-status-' + selected.status"
                        :style="`background:${selected.style.bg};border-color:${selected.style.border};color:${selected.style.text}`">
                        <span x-html="statusIcon(selected.status)"></span><span x-text="statusLabel[selected.status]"></span>
                    </span>

                    <dl class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-2 text-sm">
                        <dt class="text-gray-500">Diminta</dt>
                        <dd class="font-medium text-gray-800 break-words" x-text="selected.requested_facility.name"></dd>
                        <dt class="text-gray-500">Diberikan</dt>
                        <dd class="font-medium break-words" :class="selected.assigned_facility ? 'text-gray-800' : 'text-gray-400'"
                            x-text="selected.assigned_facility ? selected.assigned_facility.name : 'Belum ditentukan'"></dd>
                        <dt class="text-gray-500">Pemohon</dt>
                        <dd class="font-medium text-gray-800 break-words" x-text="selected.user"></dd>
                        <dt class="text-gray-500">Mulai</dt>
                        <dd class="font-medium text-gray-800" x-text="selected.start_label"></dd>
                        <dt class="text-gray-500">Selesai</dt>
                        <dd class="font-medium text-gray-800" x-text="selected.end_label"></dd>
                        <dt class="text-gray-500">Keperluan</dt>
                        <dd class="font-medium text-gray-800 break-words whitespace-pre-line" x-text="selected.keperluan || '-'"></dd>
                    </dl>

                    @if($isAdmin)
                        <template x-if="selected">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Fasilitas yang diberikan</label>
                                <select x-model="assignFacilityId"
                                    class="w-full px-3 py-2.5 bg-white border border-slate-200 rounded-xl text-sm text-slate-800 focus:outline-none focus:border-bps-blue focus:ring-4 focus:ring-bps-blue/10 cursor-pointer">
                                    <template x-for="f in (config.facilities[selected.type.code] || [])" :key="f.id">
                                        <option :value="f.id" :selected="f.id === assignFacilityId"
                                            x-text="f.name + (f.id === selected.requested_facility.id ? ' (diminta)' : '')"></option>
                                    </template>
                                </select>
                                <p class="text-[11px] text-gray-400 mt-1">Pilih fasilitas yang sama atau fasilitas lain yang sejenis. Keputusan dapat diubah kembali.</p>
                            </div>
                        </template>
                    @endif
                </div>

                {{-- AKSI DI BAGIAN BAWAH DIALOG --}}
                <div class="flex flex-wrap items-center justify-end gap-2 px-5 sm:px-6 py-4 bg-slate-50 border-t border-gray-100 rounded-b-2xl">
                    <div x-show="selected.can_edit || selected.can_delete" class="flex gap-2 w-full sm:w-auto sm:mr-auto">
                        <button type="button" x-show="selected.can_edit" @click="editBooking(selected)"
                            class="flex-1 sm:flex-none px-4 py-2 rounded-xl text-sm font-semibold text-bps-blue-dark bg-white border border-slate-300 hover:bg-slate-50 shadow-sm cursor-pointer">
                            Edit Peminjaman
                        </button>
                        <button type="button" x-show="selected.can_delete" @click="deleteBooking(selected)" :disabled="acting"
                            class="flex-1 sm:flex-none px-4 py-2 rounded-xl text-sm font-semibold text-rose-600 bg-white border border-rose-200 hover:bg-rose-50 shadow-sm cursor-pointer disabled:opacity-50">
                            Hapus
                        </button>
                    </div>
                    @if($isAdmin)
                        <div class="flex gap-2 w-full sm:w-auto">
                            <button type="button" @click="setStatus(selected, 'ditolak')" :disabled="acting"
                                class="flex-1 sm:flex-none px-4 py-2 rounded-xl text-sm font-semibold bg-gradient-to-r from-rose-500 to-rose-600 text-white shadow-sm cursor-pointer disabled:opacity-50">
                                Tolak
                            </button>
                            <button type="button" @click="setStatus(selected, 'disetujui', assignFacilityId)" :disabled="acting"
                                class="flex-1 sm:flex-none px-4 py-2 rounded-xl text-sm font-semibold bg-gradient-to-r from-bps-blue to-bps-blue-dark text-white shadow-sm cursor-pointer disabled:opacity-50">
                                Setujui
                            </button>
                        </div>
                    @else
                        <template x-if="!selected.can_edit">
                            <button type="button" @click="selected = null"
                                class="w-full sm:w-auto px-4 py-2 rounded-xl text-sm font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 cursor-pointer">
                                Tutup
                            </button>
                        </template>
                    @endif
                </div>
            </div>
        </template>
    </div>

    {{-- ─── DIALOG KONFIRMASI (PENGGANTI DIALOG BAWAAN BROWSER) ─── --}}
    <div x-show="confirmBox.open" x-cloak
        @keydown.escape.window="confirmBox.open && answer(false)"
        @click.self="answer(false)"
        class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/50 p-4">
        <div class="bg-white rounded-2xl shadow-xl border border-gray-200 w-full max-w-sm p-5 sm:p-6 space-y-4" x-show="confirmBox.open" x-transition>
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0"
                    :class="confirmBox.tone === 'danger' ? 'bg-rose-50 text-rose-600' : 'bg-blue-50 text-bps-blue'">
                    <svg x-show="confirmBox.tone === 'danger'" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    <svg x-show="confirmBox.tone !== 'danger'" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                </div>
                <div class="min-w-0">
                    <h3 class="text-base font-bold text-bps-blue-dark" x-text="confirmBox.title"></h3>
                    <p class="text-sm text-gray-600 mt-1 break-words" x-text="confirmBox.message"></p>
                </div>
            </div>
            <div class="flex gap-2 justify-end">
                <button type="button" @click="answer(false)"
                    class="flex-1 sm:flex-none px-4 py-2 rounded-xl text-sm font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 cursor-pointer">Batal</button>
                <button type="button" @click="answer(true)" x-ref="confirmOk"
                    :class="confirmBox.tone === 'danger' ? 'from-rose-500 to-rose-600' : 'from-bps-blue to-bps-blue-dark'"
                    class="flex-1 sm:flex-none px-4 py-2 rounded-xl text-sm font-semibold text-white bg-gradient-to-r shadow-sm cursor-pointer"
                    x-text="confirmBox.confirmLabel"></button>
            </div>
        </div>
    </div>

    @unless($isGuest)
    {{-- ─── MODAL FORM BUAT / EDIT PEMINJAMAN (PILIH JENIS → ISI FORM) ─── --}}
    <div x-data="bookingForm(@js([
            'facilities' => $facilities,
            'types'      => $types->keyBy('code')->map->only(['name', 'icon', 'color']),
            'storeUrl'   => route('peminjaman.store'),
            'csrf'       => csrf_token(),
            'serverNow'  => now()->getTimestampMs(), // jam server saat halaman dibuka
            'timezone'   => config('app.timezone'),
        ]))"
        @open-booking-modal.window="openForm($event.detail)"
        @keydown.escape.window="open = false"
        @click.self="open = false"
        x-show="open" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4">

        <div class="bg-white rounded-2xl shadow-xl border border-gray-200 w-full max-w-md p-5 sm:p-6 max-h-[90dvh] overflow-y-auto">

            {{-- HEADER MODAL --}}
            <div class="flex items-start justify-between mb-5">
                <div>
                    <h2 class="text-lg font-bold text-bps-blue-dark"
                        x-text="editUrl ? 'Edit Peminjaman' : (jenis ? 'Peminjaman ' + types[jenis].name : 'Buat Peminjaman')"></h2>
                    <p class="text-xs text-gray-400"
                        x-text="jenis ? 'Lengkapi detail reservasi fasilitas' : 'Pilih jenis fasilitas yang ingin dipinjam'"></p>
                </div>
                <button type="button" @click="open = false" class="text-gray-400 hover:text-gray-700 cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- LANGKAH 1: PILIH JENIS FASILITAS --}}
            <div x-show="!jenis" class="grid grid-cols-[repeat(auto-fit,minmax(120px,1fr))] gap-3">
                @foreach($types as $facilityType)
                    <button type="button" @click="jenis = @js($facilityType->code)"
                        class="flex flex-col items-center gap-2 p-5 rounded-xl border-2 transition cursor-pointer hover:shadow-md"
                        style="border-color:{{ $facilityType->color }}33;background:{{ $facilityType->color }}12">
                        <span class="text-3xl">{{ $facilityType->icon }}</span>
                        <span class="text-sm font-bold" style="color:{{ $facilityType->color }}">{{ $facilityType->name }}</span>
                    </button>
                @endforeach
            </div>

            {{-- LANGKAH 2: FORM SESUAI JENIS --}}
            <template x-if="jenis">
                <form @submit.prevent="submit($event.target)" class="space-y-4">
                    <template x-if="editUrl">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    {{-- Pesan umum (mis. kesalahan server) --}}
                    <div x-show="formError" x-text="formError" class="bg-rose-50 border border-rose-200 text-rose-700 px-3 py-2 rounded-xl text-xs font-medium"></div>

                    {{-- 1. PILIH MOBIL / RUANGAN --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1" x-text="jenis === 'car' ? 'Mobil (Plat Nomor)' : 'Ruangan'"></label>
                        <select name="facility_request_id" required @change="clearError('facility_request_id')"
                            :class="errors.facility_request_id ? 'border-red-500' : 'border-slate-200'"
                            class="w-full px-3 py-2.5 bg-white border rounded-xl text-sm text-slate-800 focus:outline-none focus:border-bps-blue focus:ring-4 focus:ring-bps-blue/10 transition-all cursor-pointer">
                            <option value="" disabled :selected="!(items[jenis] || []).some(f => f.id === oldItem)" x-text="jenis === 'car' ? '— Pilih Mobil —' : '— Pilih Ruangan —'"></option>
                            <template x-for="facility in items[jenis] || []" :key="facility.id">
                                <option :value="facility.id" x-text="facility.name" :selected="facility.id === oldItem"></option>
                            </template>
                        </select>
                        <span x-show="errors.facility_request_id" x-text="errors.facility_request_id && errors.facility_request_id[0]" class="text-xs text-red-500 font-semibold mt-1 block"></span>

                        {{-- Peminjaman disetujui yang bentrok dengan waktu yang diminta --}}
                        <template x-if="conflicts.length">
                            <div class="mt-2 rounded-xl border border-rose-200 bg-rose-50 p-3 space-y-2">
                                <p class="text-[11px] font-bold text-rose-700 uppercase">Peminjaman disetujui yang bentrok</p>
                                <template x-for="c in conflicts" :key="c.id">
                                    <div class="text-xs text-rose-800 border-t border-rose-100 pt-2 first-of-type:border-0 first-of-type:pt-0">
                                        <p class="font-semibold" x-text="c.facility + ' · ' + c.user"></p>
                                        <p x-text="c.start_label + ' – ' + c.end_label"></p>
                                        <p class="text-rose-700/80 break-words" x-show="c.keperluan" x-text="c.keperluan"></p>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>

                    {{-- 2. WAKTU MULAI --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1" x-text="jenis === 'car' ? 'Waktu Berangkat' : 'Waktu Mulai Rapat'"></label>
                        <input type="datetime-local" name="waktu_mulai" required x-model="mulai" @input="clearError('waktu_mulai')" :min="minMulai"
                            :class="errors.waktu_mulai ? 'border-red-500' : 'border-gray-300'"
                            class="w-full px-3 py-2 border rounded-xl text-sm focus:outline-none focus:border-bps-blue focus:ring-4 focus:ring-bps-blue/10">
                        <span x-show="errors.waktu_mulai" x-text="errors.waktu_mulai && errors.waktu_mulai[0]" class="text-xs text-red-500 font-semibold mt-1 block"></span>
                    </div>

                    {{-- 3. WAKTU SELESAI --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1" x-text="jenis === 'car' ? 'Waktu Kembali' : 'Waktu Selesai Rapat'"></label>
                        <input type="datetime-local" name="waktu_selesai" required x-model="selesai" @input="clearError('waktu_selesai')"
                            :min="mulai || null" :max="mulai ? mulai.slice(0, 10) + 'T23:59' : null"
                            :class="errors.waktu_selesai ? 'border-red-500' : 'border-gray-300'"
                            class="w-full px-3 py-2 border rounded-xl text-sm focus:outline-none focus:border-bps-blue focus:ring-4 focus:ring-bps-blue/10">
                        <span x-show="errors.waktu_selesai" x-text="errors.waktu_selesai && errors.waktu_selesai[0]" class="text-xs text-red-500 font-semibold mt-1 block"></span>
                    </div>

                    {{-- 4. KEPERLUAN --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1" x-text="jenis === 'car' ? 'Tujuan / Keperluan Perjalanan' : 'Agenda Rapat'"></label>
                        <textarea name="keperluan" rows="3" required @input="clearError('keperluan')" :placeholder="jenis === 'car' ? 'Tuliskan tujuan dan keperluan perjalanan...' : 'Tuliskan agenda rapat...'"
                            :class="errors.keperluan ? 'border-red-500' : 'border-gray-300'"
                            class="w-full px-3 py-2 border rounded-xl text-sm focus:outline-none focus:border-bps-blue focus:ring-4 focus:ring-bps-blue/10" x-model="keperluan"></textarea>
                        <span x-show="errors.keperluan" x-text="errors.keperluan && errors.keperluan[0]" class="text-xs text-red-500 font-semibold mt-1 block"></span>
                    </div>

                    <div class="flex gap-2">
                        <button type="button" @click="jenis = ''; resetErrors()"
                            class="px-4 py-2.5 rounded-xl text-sm font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 transition cursor-pointer">
                            Kembali
                        </button>
                        <button type="submit" :disabled="saving" class="flex-1 py-2.5 rounded-xl text-sm font-semibold bg-gradient-to-r from-bps-blue to-bps-blue-dark hover:from-bps-blue-dark hover:to-bps-blue text-white transition shadow-[0_8px_24px_-12px_rgba(0,61,130,0.8)] cursor-pointer disabled:opacity-60"
                            x-text="saving ? 'Menyimpan…' : (editUrl ? 'Simpan Perubahan' : @js($isAdmin ? 'Simpan Peminjaman' : 'Kirim Pengajuan'))">
                        </button>
                    </div>
                </form>
            </template>
        </div>
    </div>
    @endunless
</div>

{{-- SCRIPT INSTANSIASIONAL FULLCALENDAR --}}
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>

{{-- Library Tambahan Popper & Tippy untuk Hover Info Kustom --}}
<script src="https://unpkg.com/@popperjs/core@2"></script>
<script src="https://unpkg.com/tippy.js@6"></script>

<script>
    const statusLabel = { pending: 'Menunggu', disetujui: 'Disetujui', ditolak: 'Ditolak' };
    const statusIcons = @json($statusIcons);

    function statusIcon(status) {
        return `<svg class="booking-status-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="${statusIcons[status]}"/></svg>`;
    }

    // Komponen form buat / edit peminjaman: disimpan lewat API JSON tanpa reload halaman
    function bookingForm(config) {
        return {
            open: false,
            jenis: '',
            items: config.facilities,
            types: config.types,
            oldItem: '',      // fasilitas yang dipilih saat form dibuka (mode edit)
            mulai: '',
            selesai: '',
            keperluan: '',
            editUrl: null,    // terisi = mode edit
            minMulai: '',     // batas minimal waktu mulai = hari ini 00:00 menurut tanggal server (jam tidak dibatasi)
            selisihJam: config.serverNow - Date.now(), // koreksi jika jam perangkat berbeda dengan server
            saving: false,
            errors: {},       // { field: [pesan] } dari validasi server
            conflicts: [],    // peminjaman disetujui yang bentrok
            formError: '',

            // detail kosong = buat baru; detail.booking = edit peminjaman yang ada
            openForm(detail) {
                const b = detail && detail.booking;
                this.editUrl = b ? b.url : null;
                this.jenis = b ? b.type : '';
                this.oldItem = b ? b.facility_request_id : '';
                this.mulai = b ? b.waktu_mulai : '';
                this.selesai = b ? b.waktu_selesai : '';
                this.keperluan = b ? (b.keperluan || '') : '';
                this.minMulai = this.serverToday() + 'T00:00';
                this.resetErrors();
                this.open = true;
            },

            // Tanggal hari ini menurut server, dalam zona waktu server (YYYY-MM-DD)
            serverToday() {
                const parts = Object.fromEntries(new Intl.DateTimeFormat('en-CA', {
                    timeZone: config.timezone, year: 'numeric', month: '2-digit', day: '2-digit',
                }).formatToParts(new Date(Date.now() + this.selisihJam)).map(p => [p.type, p.value]));
                return `${parts.year}-${parts.month}-${parts.day}`;
            },

            resetErrors() {
                this.errors = {};
                this.conflicts = [];
                this.formError = '';
            },

            clearError(field) {
                const fields = [field];
                // bentrok berlaku untuk kombinasi fasilitas + waktu: mengubah salah satunya menghapus pesan & daftar bentrok
                if (this.conflicts.length && ['facility_request_id', 'waktu_mulai', 'waktu_selesai'].includes(field)) {
                    fields.push('facility_request_id');
                    this.conflicts = [];
                }
                this.errors = Object.fromEntries(Object.entries(this.errors).filter(([key]) => !fields.includes(key)));
            },

            async submit(form) {
                this.saving = true;
                this.resetErrors();
                try {
                    const response = await fetch(this.editUrl || config.storeUrl, {
                        method: 'POST', // mode edit: _method=PUT ikut di form
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': config.csrf },
                        body: new FormData(form),
                    });
                    const json = await response.json().catch(() => ({}));

                    if (response.status === 422) {
                        this.errors = json.errors || {};
                        this.conflicts = json.conflicts || [];
                        return;
                    }
                    if (!response.ok) throw new Error(json.message || 'Gagal menyimpan peminjaman. Silakan coba lagi.');

                    this.open = false;
                    this.$dispatch('booking-saved', { message: json.message });
                } catch (e) {
                    this.formError = e.message;
                } finally {
                    this.saving = false;
                }
            },
        };
    }

    // Komponen halaman: filter & periode memanggil API JSON, kalender & daftar memakai data yang sama
    function bookingPage(config) {
        let calendar = null; // disimpan di luar state Alpine agar tidak dibungkus proxy
        let requestSeq = 0;  // abaikan respons lama jika pengguna berpindah periode/filter dengan cepat

        const pad = (n) => String(n).padStart(2, '0');
        const toDateParam = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;

        function toEvent(b) {
            return {
                id: b.id,
                title: b.type.icon + ' ' + b.facility,
                start: b.start,
                end: b.end,
                backgroundColor: b.style.bg,
                borderColor: b.style.border,
                textColor: b.style.text,
                classNames: ['booking-status-' + b.status],
                extendedProps: { booking: b },
            };
        }

        return {
            tab: config.tab,
            filters: { type: config.type, status: config.status, q: config.q, mine: config.mine },
            mode: config.mode,
            title: '',
            range: null,      // rentang yang tampil di kalender (termasuk tanggal bulan lain di tampilan bulan)
            listRange: null,  // rentang periode sebenarnya (minggu / bulan) untuk tampilan daftar
            bookings: [],
            loading: false,
            error: '',
            notice: '',
            selected: null, // peminjaman yang dibuka di dialog detail
            assignFacilityId: '', // admin: fasilitas yang diberikan (default = yang diminta)
            acting: false,
            confirmBox: { open: false, title: '', message: '', confirmLabel: '', tone: 'primary', resolve: null },
            config,

            sort: { key: 'start', dir: 'asc' },
            sortOptions: [
                { value: 'start:asc', label: 'Waktu (paling awal)' },
                { value: 'start:desc', label: 'Waktu (paling akhir)' },
                { value: 'user:asc', label: 'Pemohon (A–Z)' },
                { value: 'user:desc', label: 'Pemohon (Z–A)' },
                { value: 'facility:asc', label: 'Fasilitas (A–Z)' },
                { value: 'facility:desc', label: 'Fasilitas (Z–A)' },
                { value: 'status:asc', label: 'Status (menunggu dulu)' },
                { value: 'status:desc', label: 'Status (ditolak dulu)' },
            ],

            // Daftar = peminjaman pada periode sebenarnya, diurutkan sesuai pilihan
            get listBookings() {
                if (!this.listRange) return [];
                const statusOrder = { pending: 0, disetujui: 1, ditolak: 2 };
                const value = {
                    start: (b) => new Date(b.start).getTime(),
                    user: (b) => b.user.toLowerCase(),
                    facility: (b) => b.facility.toLowerCase(),
                    status: (b) => statusOrder[b.status],
                }[this.sort.key];
                const dir = this.sort.dir === 'asc' ? 1 : -1;

                return this.bookings
                    .filter(b => new Date(b.start) < this.listRange.end && new Date(b.end) > this.listRange.start)
                    .sort((a, b) => {
                        const va = value(a), vb = value(b);
                        if (va < vb) return -dir;
                        if (va > vb) return dir;
                        return new Date(a.start) - new Date(b.start); // urutan kedua: waktu mulai
                    });
            },

            // Klik judul kolom: kolom sama = balik arah, kolom lain = mulai dari naik
            sortBy(key) {
                this.sort = { key, dir: this.sort.key === key && this.sort.dir === 'asc' ? 'desc' : 'asc' };
            },

            setSort(value) {
                const [key, dir] = value.split(':');
                this.sort = { key, dir };
            },

            sortIcon(key) {
                if (this.sort.key !== key) return '↕';
                return this.sort.dir === 'asc' ? '▲' : '▼';
            },

            init() {
                calendar = new FullCalendar.Calendar(this.$refs.calendar, {
                    initialView: config.mode === 'month' ? 'dayGridMonth' : 'timeGridWeek',
                    initialDate: config.date || undefined,
                    locale: 'id',
                    timeZone: 'local',
                    headerToolbar: false, // diganti toolbar periode bersama di atas
                    eventDisplay: 'block',
                    // Tampilan bulan: tampilkan jam selesai juga (09:00 - 11:00)
                    views: { dayGridMonth: { displayEventEnd: true } },
                    // Isi item: jam + ikon status + judul (judul lewat textContent agar aman)
                    eventContent: function(arg) {
                        const wrap = document.createElement('div');
                        wrap.className = 'booking-event-content';
                        if (arg.timeText) {
                            const time = document.createElement('div');
                            time.textContent = arg.timeText;
                            wrap.appendChild(time);
                        }
                        const title = document.createElement('div');
                        title.className = 'booking-event-title';
                        title.innerHTML = statusIcon(arg.event.extendedProps.booking.status);
                        title.append(arg.event.title);
                        wrap.appendChild(title);
                        return { domNodes: [wrap] };
                    },
                    eventTimeFormat: {
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: false
                    },
                    slotLabelFormat: {
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: false
                    },
                    eventDidMount: function(info) {
                        const b = info.event.extendedProps.booking;
                        const content = document.createElement('div');
                        content.className = 'p-2 space-y-1.5 max-w-[280px] text-left';
                        content.innerHTML = `<p class="font-bold border-b border-slate-100 pb-1 text-bps-blue-dark text-xs">📄 Rincian Peminjaman</p>`;

                        // Isi teks lewat textContent agar input pengguna tidak dieksekusi sebagai HTML
                        [
                            ['Jenis', b.type.label],
                            ['Diminta', b.requested_facility.name],
                            ['Diberikan', b.assigned_facility ? b.assigned_facility.name : 'Belum ditentukan'],
                            ['Mulai', b.start_label],
                            ['Selesai', b.end_label],
                            ['Pemohon', b.user],
                            ['Status', statusLabel[b.status] || b.status],
                            ['Keperluan', b.keperluan || '-'],
                        ].forEach(([label, value]) => {
                            const row = document.createElement('p');
                            row.className = 'text-[11px] leading-relaxed text-slate-700 font-medium whitespace-normal break-words';
                            row.innerHTML = `<strong class="text-slate-500">${label}:</strong> `;
                            row.append(value);
                            content.appendChild(row);
                        });

                        tippy(info.el, {
                            content: content,
                            placement: 'top',
                            theme: 'bps-light',
                            animation: 'scale',
                            delay: [50, 0],
                            touch: false, // di layar sentuh, ketukan langsung membuka dialog detail
                        });
                    },
                    // Klik item kalender membuka dialog detail / persetujuan
                    eventClick: (info) => this.openDetail(info.event.extendedProps.booking),
                    // Dipanggil setiap periode berubah (prev/next/hari ini/bulan/minggu)
                    datesSet: (info) => {
                        this.title = info.view.title;
                        this.mode = info.view.type === 'dayGridMonth' ? 'month' : 'week';
                        this.range = { start: info.startStr, end: info.endStr };
                        this.listRange = { start: info.view.currentStart, end: info.view.currentEnd };
                        this.load();
                    },
                });
                calendar.render();
            },

            async load() {
                if (!this.range) return;
                const seq = ++requestSeq;
                this.loading = true;
                this.error = '';

                const params = new URLSearchParams({ start: this.range.start, end: this.range.end });
                if (this.filters.type) params.set('type', this.filters.type);
                if (this.filters.status) params.set('status', this.filters.status);
                if (this.filters.q.trim()) params.set('q', this.filters.q.trim());
                if (this.filters.mine) params.set('mine', '1');

                try {
                    const response = await fetch(`${config.dataUrl}?${params}`, { headers: { 'Accept': 'application/json' } });
                    if (!response.ok) throw new Error(response.status);
                    const json = await response.json();
                    if (seq !== requestSeq) return;

                    this.bookings = json.data;
                    calendar.removeAllEvents();
                    calendar.addEventSource(this.bookings.map(toEvent));
                    this.syncUrl();
                } catch (e) {
                    if (seq === requestSeq) this.error = 'Gagal memuat data peminjaman. Silakan coba lagi.';
                } finally {
                    if (seq === requestSeq) this.loading = false;
                }
            },

            prev() { calendar.prev(); },
            next() { calendar.next(); },
            today() { calendar.today(); },
            setMode(mode) { calendar.changeView(mode === 'month' ? 'dayGridMonth' : 'timeGridWeek'); },

            showTab(tab) {
                this.tab = tab;
                this.syncUrl();
                if (tab === 'calendar') this.$nextTick(() => calendar.updateSize());
            },

            // Tooltip untuk tombol ikon di kolom aksi
            tip(el, text) {
                tippy(el, { content: text, placement: 'top', theme: 'bps-light tip', delay: [150, 0], touch: false });
            },

            // Dialog detail / persetujuan; fasilitas default = yang sudah diberikan, atau yang diminta
            openDetail(booking) {
                this.selected = booking;
                this.assignFacilityId = (booking.assigned_facility || booking.requested_facility).id;
            },

            // Dialog konfirmasi aplikasi; resolve true jika pengguna menekan tombol konfirmasi
            ask({ title, message, confirmLabel, tone = 'primary' }) {
                return new Promise((resolve) => {
                    this.confirmBox = { open: true, title, message, confirmLabel, tone, resolve };
                    this.$nextTick(() => this.$refs.confirmOk && this.$refs.confirmOk.focus());
                });
            },

            answer(ok) {
                const resolve = this.confirmBox.resolve;
                this.confirmBox = { ...this.confirmBox, open: false, resolve: null };
                if (resolve) resolve(ok);
            },

            // Admin: setujui / tolak dari dialog persetujuan lewat API, lalu muat ulang data periode ini.
            // Berlaku untuk semua status (keputusan boleh diubah). facilityId = fasilitas yang diberikan.
            async setStatus(booking, status, facilityId = null) {
                const approve = status === 'disetujui';
                const given = (approve && facilityId && (config.facilities[booking.type.code] || []).find(f => f.id === facilityId))
                    || booking.assigned_facility || booking.requested_facility;

                const ok = await this.ask(approve
                    ? {
                        title: 'Setujui peminjaman?',
                        message: `${booking.user} meminta ${booking.requested_facility.name} (${booking.start_label} – ${booking.end_label}). `
                            + `Fasilitas yang diberikan: ${given.name}.`,
                        confirmLabel: 'Ya, Setujui',
                    }
                    : {
                        title: 'Tolak peminjaman?',
                        message: `Pengajuan ${booking.requested_facility.name} oleh ${booking.user} (${booking.start_label} – ${booking.end_label}) akan ditolak.`,
                        confirmLabel: 'Ya, Tolak',
                        tone: 'danger',
                    });
                if (!ok) return;

                this.acting = true;
                let gagal = '';
                try {
                    const response = await fetch(booking.status_url, {
                        method: 'PATCH',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': config.csrf,
                        },
                        body: JSON.stringify(approve && facilityId ? { status, facility_id: facilityId } : { status }),
                    });
                    const json = await response.json().catch(() => ({}));
                    if (!response.ok) throw new Error(json.message || 'Gagal memperbarui status peminjaman. Silakan coba lagi.');
                    this.notice = json.message;
                    setTimeout(() => this.notice = '', 4000);
                } catch (e) {
                    this.notice = '';
                    gagal = e.message; // mis. sudah diproses admin lain / fasilitas sudah terpakai
                } finally {
                    this.acting = false;
                    this.selected = null;
                }

                // Selalu muat ulang agar status di layar sesuai data terbaru; pesan gagal ditampilkan setelahnya
                // (load() mengosongkan pesan error lama)
                await this.load();
                if (gagal) this.error = gagal;
            },

            // Form buat / edit tersimpan lewat API: tampilkan pesan & muat ulang data periode ini
            bookingSaved(message) {
                this.notice = message;
                setTimeout(() => this.notice = '', 4000);
                this.load();
            },

            // Hapus peminjaman lewat API setelah konfirmasi, lalu muat ulang data periode ini
            async deleteBooking(booking) {
                const ok = await this.ask({
                    title: 'Hapus peminjaman?',
                    message: `Peminjaman ${booking.facility} oleh ${booking.user} (${booking.start_label} – ${booking.end_label}) akan dihapus permanen.`,
                    confirmLabel: 'Ya, Hapus',
                    tone: 'danger',
                });
                if (!ok) return;

                this.acting = true;
                let gagal = '';
                try {
                    const response = await fetch(booking.delete_url, {
                        method: 'DELETE',
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': config.csrf },
                    });
                    const json = await response.json().catch(() => ({}));
                    if (!response.ok) throw new Error(json.message || 'Gagal menghapus peminjaman. Silakan coba lagi.');
                    this.notice = json.message;
                    setTimeout(() => this.notice = '', 4000);
                } catch (e) {
                    this.notice = '';
                    gagal = e.message;
                } finally {
                    this.acting = false;
                    this.selected = null;
                }

                await this.load();
                if (gagal) this.error = gagal;
            },

            // Buka form edit berisi data peminjaman milik sendiri (dari dialog detail maupun daftar)
            editBooking(booking) {
                this.selected = null;
                this.$dispatch('open-booking-modal', { booking: booking.edit });
            },

            resetFilters() {
                this.filters = { type: '', status: '', q: '', mine: false };
                this.load();
            },

            // Simpan kondisi tampilan di URL agar tetap sama setelah reload / submit form
            syncUrl() {
                const params = new URLSearchParams();
                if (this.tab === 'list') params.set('view', 'list');
                if (this.filters.type) params.set('type', this.filters.type);
                if (this.filters.status) params.set('status', this.filters.status);
                if (this.filters.q.trim()) params.set('q', this.filters.q.trim());
                if (this.filters.mine) params.set('mine', '1');
                params.set('mode', this.mode);
                params.set('date', toDateParam(calendar.getDate()));
                history.replaceState(null, '', `${location.pathname}?${params}`);
            },
        };
    }
</script>

<style>
    [x-cloak] { display: none !important; }
    .fc { font-family: inherit; --fc-border-color: #e2e8f0; } /* sama dengan garis strip legenda */
    .period-btn { display: inline-flex; align-items: center; justify-content: center; height: 32px; min-width: 32px; border-radius: 8px; background: #043264; color: #fff; font-size: 11px; font-weight: 600; cursor: pointer; transition: background-color .15s; }
    .period-btn:hover { background: #05417c; }
    .fc-event { border-radius: 6px; padding: 3px 6px; font-size: 11px; font-weight: 500; cursor: pointer; }
    .booking-badge { display: inline-block; padding: 2px 10px; border-radius: 9999px; border: 1px solid; font-size: 11px; font-weight: 600; }
    .booking-status-disetujui, .booking-status-pending, .booking-status-ditolak { border-width: 2px !important; border-style: solid !important; }
    .booking-status-pending { border-style: dashed !important; }
    .booking-status-ditolak { text-decoration: line-through; }
    .booking-status-icon { display: inline-block; width: 12px; height: 12px; vertical-align: -2px; margin-right: 3px; flex-shrink: 0; }
    .booking-event-content { overflow: hidden; }
    .booking-event-title { font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

    /* Desain Balon Tooltip Tippy.js */
    .tippy-box[data-theme~='bps-light'] {
        background-color: #ffffff;
        color: #334155;
        border-radius: 10px;
        box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.15), 0 4px 6px -2px rgba(15, 23, 42, 0.05);
        border: 1px solid #e2e8f0;
    }
    .tippy-box[data-theme~='tip'] .tippy-content { padding: 4px 8px; font-size: 11px; font-weight: 600; }
    .tippy-box[data-theme~='bps-light'] .tippy-arrow {
        color: #ffffff;
    }
</style>
@endsection
