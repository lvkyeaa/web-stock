@extends('layouts.admin')
@section('title', 'Dashboard')

@section('content')
    <div class="space-y-6">
        {{-- Header --}}
        <div class="rounded-2xl border border-slate-200/70 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-slate-500">Selamat datang kembali,</p>
                    <h2 class="mt-1 text-xl font-semibold tracking-tight text-slate-900">{{ auth()->user()->username }} 👋</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ now()->translatedFormat('l') }}, {{ now()->tanggal() }}</p>
                </div>
            </div>
        </div>

        <div>
            <div class="mb-3 flex items-center gap-2">
                <span class="inline-block h-4 w-1.5 rounded-full bg-bps-orange"></span>
                <h3 class="text-xs font-semibold uppercase tracking-[0.2em] text-bps-blue-dark">Ringkasan Operasional</h3>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-2xl border border-slate-200/70 bg-white p-4 shadow-sm">
                    <div class="mb-3 flex items-center justify-between">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-orange-50/60">
                            <svg class="h-5 w-5 text-bps-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                        </div>
                        <span class="rounded-full bg-orange-50 px-2 py-1 text-[9px] font-semibold uppercase tracking-[0.2em] text-bps-orange">Total</span>
                    </div>
                    <p class="text-xl font-semibold tracking-tight text-slate-800">{{ $stats['total_barang'] }}</p>
                    <p class="mt-1 text-[11px] font-semibold text-slate-400">Persediaan</p>
                </div>

                <div class="rounded-2xl border border-slate-200/70 bg-white p-4 shadow-sm">
                    <div class="mb-3 flex items-center justify-between">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100">
                            <svg class="h-5 w-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                        <span class="rounded-full bg-slate-100 px-2 py-1 text-[9px] font-semibold uppercase tracking-[0.2em] text-slate-500">User</span>
                    </div>
                    <p class="text-xl font-semibold tracking-tight text-slate-800">{{ $stats['total_users'] }}</p>
                    <p class="mt-1 text-[11px] font-semibold text-slate-400">Pengguna</p>
                </div>

                <div class="rounded-2xl border border-slate-200/70 bg-white p-4 shadow-sm">
                    <div class="mb-3 flex items-center justify-between">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50">
                            <svg class="h-5 w-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <span class="rounded-full bg-amber-50 px-2 py-1 text-[9px] font-semibold uppercase tracking-[0.2em] text-amber-700">Pending</span>
                    </div>
                    <p class="text-xl font-semibold tracking-tight text-slate-800">{{ $stats['pending'] }}</p>
                    <p class="mt-1 text-[11px] font-semibold text-slate-400">Persediaan Pending</p>
                </div>

                <div class="rounded-2xl border border-slate-200/70 bg-white p-4 shadow-sm">
                    <div class="mb-3 flex items-center justify-between">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50">
                            <svg class="h-5 w-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <span class="rounded-full bg-emerald-50 px-2 py-1 text-[9px] font-semibold uppercase tracking-[0.2em] text-emerald-700">Setuju</span>
                    </div>
                    <p class="text-xl font-semibold tracking-tight text-slate-800">{{ $stats['disetujui'] }}</p>
                    <p class="mt-1 text-[11px] font-semibold text-slate-400">Persediaan Disetujui</p>
                </div>
            </div>
        </div>

        <div>
            <div class="mb-3 flex items-center gap-2">
                <span class="inline-block h-4 w-1.5 rounded-full bg-bps-orange"></span>
                <h3 class="text-xs font-semibold uppercase tracking-[0.2em] text-bps-blue-dark">Peminjaman Fasilitas</h3>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-2xl border border-slate-200/70 bg-white p-4 shadow-sm">
                    <div class="mb-3 flex items-center justify-between">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100">
                            <svg class="h-5 w-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </div>
                        <span class="rounded-full bg-slate-100 px-2 py-1 text-[9px] font-semibold uppercase tracking-[0.2em] text-slate-600">Masuk</span>
                    </div>
                    <p class="text-xl font-semibold tracking-tight text-slate-800">{{ $peminjamanStats['total'] }}</p>
                    <p class="mt-1 text-[11px] font-semibold text-slate-400">Pengajuan</p>
                </div>

                <div class="rounded-2xl border border-slate-200/70 bg-white p-4 shadow-sm">
                    <div class="mb-3 flex items-center justify-between">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50">
                            <svg class="h-5 w-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <span class="rounded-full bg-amber-50 px-2 py-1 text-[9px] font-semibold uppercase tracking-[0.2em] text-amber-700">Approval</span>
                    </div>
                    <p class="text-xl font-semibold tracking-tight text-slate-800">{{ $peminjamanStats['pending'] }}</p>
                    <p class="mt-1 text-[11px] font-semibold text-slate-400">Perlu Tindakan</p>
                </div>

                <div class="rounded-2xl border border-slate-200/70 bg-white p-4 shadow-sm">
                    <div class="mb-3 flex items-center justify-between">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50">
                            <svg class="h-5 w-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <span class="rounded-full bg-emerald-50 px-2 py-1 text-[9px] font-semibold uppercase tracking-[0.2em] text-emerald-700">Disetujui</span>
                    </div>
                    <p class="text-xl font-semibold tracking-tight text-slate-800">{{ $peminjamanStats['disetujui'] }}</p>
                    <p class="mt-1 text-[11px] font-semibold text-slate-400">Booking Aktif</p>
                </div>

                <div class="rounded-2xl border border-slate-200/70 bg-white p-4 shadow-sm">
                    <div class="mb-3 flex items-center justify-between">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-50">
                            <svg class="h-5 w-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <span class="rounded-full bg-rose-50 px-2 py-1 text-[9px] font-semibold uppercase tracking-[0.2em] text-rose-700">Ditolak</span>
                    </div>
                    <p class="text-xl font-semibold tracking-tight text-slate-800">{{ $peminjamanStats['ditolak'] }}</p>
                    <p class="mt-1 text-[11px] font-semibold text-slate-400">Booking Ditolak</p>
                </div>
            </div>
        </div>

        {{-- Quick Links --}}
        <div class="rounded-2xl border border-slate-200/70 bg-white p-4 shadow-sm">
            <h3 class="mb-3 text-[11px] font-semibold uppercase tracking-[0.2em] text-bps-blue-dark">Akses Cepat</h3>
            <div class="grid grid-cols-2 gap-2 md:grid-cols-4">
                <a href="{{ route('barang.index') }}"
                    class="flex flex-col items-center gap-2 rounded-xl border border-slate-200/70 bg-white p-3 transition hover:-translate-y-0.5 hover:shadow-sm">
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-bps-blue/10 to-bps-orange/10">
                        <svg class="h-6 w-6 text-bps-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                    <span class="text-[11px] font-semibold text-bps-blue-dark">Persediaan</span>
                </a>

                <a href="{{ route('pengajuan.index') }}"
                    class="flex flex-col items-center gap-2 rounded-xl border border-slate-200/70 bg-white p-3 transition hover:-translate-y-0.5 hover:shadow-sm">
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-bps-blue/10 to-bps-orange/10">
                        <svg class="h-6 w-6 text-bps-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                    <span class="text-[11px] font-semibold text-slate-600">Transaksi</span>
                </a>

                <a href="{{ route('admin.manajemen-user.index') }}"
                    class="flex flex-col items-center gap-2 rounded-xl border border-slate-200/70 bg-white p-3 transition hover:-translate-y-0.5 hover:shadow-sm">
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-bps-blue/10 to-bps-orange/10">
                        <svg class="h-6 w-6 text-bps-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <span class="text-[11px] font-semibold text-slate-600">User</span>
                </a>

                <a href="{{ route('peminjaman.index') }}"
                    class="flex flex-col items-center gap-2 rounded-xl border border-slate-200/70 bg-white p-3 transition hover:-translate-y-0.5 hover:shadow-sm">
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-bps-blue/10 to-bps-orange/10">
                        <svg class="h-6 w-6 text-bps-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <span class="text-[11px] font-semibold text-slate-600">Approval</span>
                </a>
            </div>
        </div>
    </div>
@endsection