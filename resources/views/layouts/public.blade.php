<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Jadwal Fasilitas') — BPS Provinsi Jawa Timur</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="{{ asset('vendor/alpinejs-3.17.4.min.js') }}"></script>
</head>

{{-- Layout publik (tanpa login): tanpa sidebar, hanya header & konten --}}
<body class="bg-bps-cream-bg font-[Plus_Jakarta_Sans] min-h-dvh">

    <header class="h-16 bg-white/90 backdrop-blur-xl border-b border-slate-200/70 shadow-sm sticky top-0 z-40">
        <div class="h-full max-w-7xl mx-auto px-4 sm:px-6 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-10 h-10 rounded-xl bg-white flex items-center justify-center flex-shrink-0 shadow-sm border border-slate-200/80">
                    <svg class="w-6 h-6 text-bps-orange" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M3 3h4v8H3zm6-4h4v12H9zm6 2h4v10h-4zm-14 15h20v2H1z" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="text-[11px] font-bold text-bps-orange uppercase tracking-[0.01em] leading-tight truncate">BPS Provinsi Jawa Timur</p>
                    <p class="text-[10px] font-medium leading-tight text-slate-500 mt-0.5 truncate">Jadwal Peminjaman Fasilitas</p>
                </div>
            </div>

            <a href="{{ route('login') }}"
                class="flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold bg-bps-orange hover:bg-bps-orange-hover text-white shadow-sm transition flex-shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                </svg>
                Masuk
            </a>
        </div>
    </header>

    <main class="max-w-7xl mx-auto p-4 sm:p-6">
        @yield('content')
    </main>
</body>

</html>
