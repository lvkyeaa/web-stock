{{-- Kartu sambutan dashboard (admin & pengguna). $pesan: kalimat di bawah nama --}}
@php
    $jam = now()->hour;
    $sapaan = match (true) {
        $jam >= 4 && $jam < 11  => 'Selamat pagi',
        $jam >= 11 && $jam < 15 => 'Selamat siang',
        $jam >= 15 && $jam < 18 => 'Selamat sore',
        default                 => 'Selamat malam',
    };
@endphp

<div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-bps-blue via-bps-blue-light to-bps-blue-dark p-6 sm:p-8 text-white shadow-[0_24px_60px_-30px_rgba(0,61,130,0.9)]">
    {{-- Ornamen latar --}}
    <div aria-hidden="true" class="pointer-events-none absolute -right-16 -top-20 h-64 w-64 rounded-full bg-bps-orange/30 blur-3xl"></div>
    <div aria-hidden="true" class="pointer-events-none absolute -bottom-24 left-1/3 h-56 w-56 rounded-full bg-white/10 blur-3xl"></div>
    <svg aria-hidden="true" class="pointer-events-none absolute right-6 bottom-0 hidden h-36 w-36 text-white/10 sm:block" fill="currentColor" viewBox="0 0 24 24">
        <path d="M3 3h4v8H3zm6-4h4v12H9zm6 2h4v10h-4zm-14 15h20v2H1z" />
    </svg>

    <div class="relative">
        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-[11px] font-semibold tracking-wide backdrop-blur">
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            {{ now()->translatedFormat('l') }}, {{ now()->tanggal() }}
        </span>
        <p class="mt-4 text-sm font-medium text-white/75">{{ $sapaan }},</p>
        <h2 class="mt-1 text-2xl sm:text-3xl font-bold tracking-tight">{{ auth()->user()->name }} <span aria-hidden="true">👋</span></h2>
        <p class="mt-2 max-w-xl text-sm leading-relaxed text-white/80">{{ $pesan }}</p>
    </div>
</div>
