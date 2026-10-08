@extends('layouts.customer')
@section('title', 'Dashboard')

@section('content')
    <div class="space-y-8">
        @include('partials.sambutan-dashboard', ['pesan' => 'Mau mengajukan persediaan atau meminjam fasilitas hari ini? Pilih layanan di bawah untuk memulai.'])

        <div>
            <div class="mb-4 flex items-center gap-2">
                <span class="inline-block h-4 w-1.5 rounded-full bg-bps-orange"></span>
                <h3 class="text-xs font-semibold uppercase tracking-[0.2em] text-bps-blue-dark">Layanan</h3>
            </div>

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                {{-- Katalog Persediaan --}}
                <a href="{{ route('barang.katalog') }}"
                    class="group relative flex flex-col overflow-hidden rounded-3xl border border-slate-200/70 bg-white p-6 sm:p-7 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-bps-blue/20 hover:shadow-[0_24px_50px_-24px_rgba(0,61,130,0.45)] focus:outline-none focus-visible:ring-2 focus-visible:ring-bps-blue/40">
                    <div aria-hidden="true" class="pointer-events-none absolute -right-12 -top-12 h-40 w-40 rounded-full bg-bps-blue/5 transition-transform duration-500 group-hover:scale-125"></div>

                    <div class="relative flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-bps-blue to-bps-blue-light text-white shadow-lg shadow-bps-blue/30 transition-transform duration-300 group-hover:scale-105">
                        <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>

                    <h4 class="relative mt-5 text-lg font-bold tracking-tight text-slate-900">Katalog Persediaan</h4>
                    <p class="relative mt-1.5 text-sm leading-relaxed text-slate-500">
                        Lihat ATK, ARK, cetakan, dan publikasi yang tersedia, lalu masukkan ke keranjang untuk diajukan.
                    </p>

                    <span class="relative mt-6 inline-flex items-center gap-1.5 text-sm font-semibold text-bps-blue">
                        Buka katalog
                        <svg class="h-4 w-4 transition-transform duration-300 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                    </span>
                </a>

                {{-- Peminjaman Fasilitas --}}
                <a href="{{ route('peminjaman.index') }}"
                    class="group relative flex flex-col overflow-hidden rounded-3xl border border-slate-200/70 bg-white p-6 sm:p-7 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-bps-orange/30 hover:shadow-[0_24px_50px_-24px_rgba(244,121,32,0.5)] focus:outline-none focus-visible:ring-2 focus-visible:ring-bps-orange/40">
                    <div aria-hidden="true" class="pointer-events-none absolute -right-12 -top-12 h-40 w-40 rounded-full bg-bps-orange/5 transition-transform duration-500 group-hover:scale-125"></div>

                    <div class="relative flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-bps-orange to-bps-orange-light text-white shadow-lg shadow-bps-orange/30 transition-transform duration-300 group-hover:scale-105">
                        <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>

                    <h4 class="relative mt-5 text-lg font-bold tracking-tight text-slate-900">Peminjaman Fasilitas</h4>
                    <p class="relative mt-1.5 text-sm leading-relaxed text-slate-500">
                        Pesan mobil dinas, ruang rapat, atau Zoom kantor, dan pantau jadwalnya di kalender.
                    </p>

                    <span class="relative mt-6 inline-flex items-center gap-1.5 text-sm font-semibold text-bps-orange">
                        Buka jadwal
                        <svg class="h-4 w-4 transition-transform duration-300 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                    </span>
                </a>
            </div>
        </div>
    </div>
@endsection
