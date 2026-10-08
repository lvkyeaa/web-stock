@extends('layouts.admin')
@section('title', 'Dashboard')

@php
    // Kartu statistik per bagian. url: kartu bisa diklik ke daftar yang sudah terfilter (null = tidak bisa diklik)
    $bagian = [
        'Persediaan' => [
            ['label' => 'Jenis Persediaan', 'nilai' => $stats['total_barang'], 'ket' => 'Terdaftar di inventaris', 'url' => route('barang.index'),
                'warna' => 'bg-bps-blue/10 text-bps-blue', 'ikon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
            ['label' => 'Stok Habis', 'nilai' => $stats['stok_habis'], 'ket' => 'Tidak bisa diajukan', 'url' => route('barang.index', ['stok_habis' => 1, 'urut' => 'nama']),
                'warna' => 'bg-rose-50 text-rose-600', 'ikon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'],
            ['label' => 'Pengajuan Menunggu', 'nilai' => $stats['pending'], 'ket' => 'Perlu disetujui / ditolak', 'url' => route('pengajuan.index', ['status' => 'pending']),
                'warna' => 'bg-amber-50 text-amber-600', 'ikon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'sorot' => $stats['pending'] > 0],
            ['label' => 'Disetujui Bulan Ini', 'nilai' => $stats['disetujui_bulan_ini'], 'ket' => now()->translatedFormat('F Y'), 'url' => route('pengajuan.index', ['status' => 'disetujui']),
                'warna' => 'bg-emerald-50 text-emerald-600', 'ikon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
        ],
        'Peminjaman Fasilitas' => [
            ['label' => 'Peminjaman Menunggu', 'nilai' => $peminjamanStats['pending'], 'ket' => 'Perlu disetujui / ditolak', 'url' => route('peminjaman.index', ['view' => 'list', 'status' => 'pending']),
                'warna' => 'bg-amber-50 text-amber-600', 'ikon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'sorot' => $peminjamanStats['pending'] > 0],
            ['label' => 'Berlangsung Hari Ini', 'nilai' => $peminjamanStats['hari_ini'], 'ket' => 'Peminjaman disetujui', 'url' => route('peminjaman.index', ['mode' => 'week', 'date' => now()->toDateString()]),
                'warna' => 'bg-bps-blue/10 text-bps-blue', 'ikon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
            ['label' => 'Disetujui', 'nilai' => $peminjamanStats['disetujui'], 'ket' => 'Total peminjaman', 'url' => route('peminjaman.index', ['view' => 'list', 'status' => 'disetujui']),
                'warna' => 'bg-emerald-50 text-emerald-600', 'ikon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
            ['label' => 'Ditolak', 'nilai' => $peminjamanStats['ditolak'], 'ket' => 'Total peminjaman', 'url' => route('peminjaman.index', ['view' => 'list', 'status' => 'ditolak']),
                'warna' => 'bg-slate-100 text-slate-500', 'ikon' => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z'],
        ],
    ];

    // Akses cepat ke halaman kelola
    $pintasan = [
        ['label' => 'Kelola Persediaan', 'url' => route('barang.index'), 'ikon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
        ['label' => 'Persetujuan Pengajuan', 'url' => route('pengajuan.index', ['status' => 'pending']), 'ikon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'],
        ['label' => 'Persetujuan Peminjaman Fasilitas', 'url' => route('peminjaman.index', ['view' => 'list', 'status' => 'pending']), 'ikon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
        ['label' => 'User Management', 'url' => route('admin.manajemen-user.index'), 'ikon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
    ];

    $menunggu = $stats['pending'] + $peminjamanStats['pending'];
@endphp

@section('content')
    <div class="space-y-8">
        @include('partials.sambutan-dashboard', ['pesan' => $menunggu > 0
            ? "Ada {$stats['pending']} pengajuan persediaan dan {$peminjamanStats['pending']} peminjaman fasilitas yang menunggu keputusan Anda."
            : 'Semua pengajuan dan peminjaman sudah ditangani. Berikut ringkasan operasional hari ini.'])

        {{-- Statistik --}}
        @foreach ($bagian as $judul => $kartu)
            <div>
                <div class="mb-4 flex items-center gap-2">
                    <span class="inline-block h-4 w-1.5 rounded-full bg-bps-orange"></span>
                    <h3 class="text-xs font-semibold uppercase tracking-[0.2em] text-bps-blue-dark">{{ $judul }}</h3>
                </div>

                <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                    @foreach ($kartu as $k)
                        <a href="{{ $k['url'] }}"
                            class="group relative overflow-hidden rounded-2xl border bg-white p-4 sm:p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-bps-blue/40 {{ ($k['sorot'] ?? false) ? 'border-amber-300 ring-1 ring-amber-200' : 'border-slate-200/70' }}">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl {{ $k['warna'] }}">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $k['ikon'] }}" />
                                    </svg>
                                </div>
                                @if ($k['sorot'] ?? false)
                                    <span class="relative flex h-2.5 w-2.5" title="Perlu tindakan">
                                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-amber-400 opacity-75"></span>
                                        <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-amber-500"></span>
                                    </span>
                                @endif
                            </div>
                            <p class="mt-4 text-2xl sm:text-3xl font-bold tracking-tight text-slate-900 tabular-nums">{{ number_format($k['nilai'], 0, ',', '.') }}</p>
                            <p class="mt-1 text-sm font-semibold text-slate-700">{{ $k['label'] }}</p>
                            <p class="mt-0.5 text-xs text-slate-400">{{ $k['ket'] }}</p>
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach

        {{-- Akses cepat --}}
        <div>
            <div class="mb-4 flex items-center gap-2">
                <span class="inline-block h-4 w-1.5 rounded-full bg-bps-orange"></span>
                <h3 class="text-xs font-semibold uppercase tracking-[0.2em] text-bps-blue-dark">Akses Cepat</h3>
                <span class="ml-auto text-xs text-slate-400">{{ number_format($stats['total_users'], 0, ',', '.') }} pengguna terdaftar</span>
            </div>

            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                @foreach ($pintasan as $p)
                    <a href="{{ $p['url'] }}"
                        class="group flex flex-col items-center gap-2.5 rounded-2xl border border-slate-200/70 bg-white p-4 text-center shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-bps-blue/20 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-bps-blue/40">
                        <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-bps-blue/10 to-bps-orange/10 transition-transform duration-200 group-hover:scale-105">
                            <svg class="h-6 w-6 text-bps-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $p['ikon'] }}" />
                            </svg>
                        </div>
                        <span class="text-xs font-semibold leading-tight text-slate-700 group-hover:text-bps-blue">{{ $p['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
@endsection
