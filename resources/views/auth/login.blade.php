<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk ke Sistem — BPS Provinsi Jawa Timur</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-bps-cream-bg font-[Plus_Jakarta_Sans] min-h-dvh flex items-center justify-center p-4 sm:p-6">

    {{-- Latar dekoratif --}}
    <div class="pointer-events-none fixed inset-0 overflow-hidden" aria-hidden="true">
        <div class="absolute -top-32 -left-32 h-96 w-96 rounded-full bg-bps-orange/15 blur-3xl"></div>
        <div class="absolute -bottom-40 -right-32 h-[28rem] w-[28rem] rounded-full bg-bps-blue/15 blur-3xl"></div>
    </div>

    <main class="relative w-full max-w-md lg:max-w-5xl grid lg:grid-cols-[1.1fr_1fr] rounded-3xl overflow-hidden bg-white shadow-[0_40px_80px_-30px_rgba(0,42,92,0.35)] border border-slate-200/70">

        {{-- ─── PANEL KIRI: PENJELASAN APLIKASI ─── --}}
        <section class="relative hidden lg:block overflow-hidden bg-gradient-to-br from-bps-blue-dark via-bps-blue to-bps-blue-light p-10 text-white">
            <div class="pointer-events-none absolute -top-24 -right-24 h-72 w-72 rounded-full bg-bps-orange/30 blur-3xl" aria-hidden="true"></div>
            <div class="pointer-events-none absolute -bottom-28 -left-16 h-72 w-72 rounded-full bg-white/10 blur-3xl" aria-hidden="true"></div>

            <div class="relative flex h-full flex-col gap-8">
                {{-- Identitas --}}
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white shadow-md">
                        <svg class="h-6 w-6 text-bps-orange" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M3 3h4v8H3zm6-4h4v12H9zm6 2h4v10h-4zm-14 15h20v2H1z" />
                        </svg>
                    </div>
                    <div class="leading-tight">
                        <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-bps-orange-light">BPS Provinsi Jawa Timur</p>
                        <p class="text-sm font-semibold text-white/90">Layanan Fasilitas &amp; Persediaan</p>
                    </div>
                </div>

                {{-- Judul --}}
                <div class="space-y-3">
                    <h1 class="text-3xl font-extrabold leading-tight tracking-tight">
                        Pinjam fasilitas &amp; ajukan persediaan,
                        <span class="bg-gradient-to-r from-bps-orange-light to-amber-200 bg-clip-text text-transparent">lebih mudah.</span>
                    </h1>
                    <p class="text-sm text-white/75 max-w-md">Satu aplikasi untuk seluruh kebutuhan operasional kantor, kini dibantu Asisten AI.</p>
                </div>

                {{-- Fitur --}}
                <ul class="space-y-3">
                    <li class="flex items-start gap-3 rounded-2xl bg-white/10 p-3.5 ring-1 ring-white/15 backdrop-blur-sm">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white/15">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </span>
                        <p class="text-xs text-white/70"><strong class="block text-sm text-white">Peminjaman Fasilitas</strong>Pinjam mobil dinas, ruang rapat, dan fasilitas kantor lainnya, lalu pantau jadwalnya.</p>
                    </li>
                    <li class="flex items-start gap-3 rounded-2xl bg-white/10 p-3.5 ring-1 ring-white/15 backdrop-blur-sm">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white/15">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                        </span>
                        <p class="text-xs text-white/70"><strong class="block text-sm text-white">Pengajuan Persediaan</strong>Pilih persediaan dari katalog, masukkan ke keranjang, dan ajukan permintaan.</p>
                    </li>
                    <li class="flex items-start gap-3 rounded-2xl bg-gradient-to-r from-bps-orange/30 to-white/10 p-3.5 ring-1 ring-bps-orange-light/40 backdrop-blur-sm">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-bps-orange to-bps-orange-light shadow-md">
                            @include('partials.asisten-ai.ikon', ['class' => 'h-5 w-5'])
                        </span>
                        <p class="text-xs text-white/70">
                            <strong class="flex items-center gap-2 text-sm text-white">
                                Dilengkapi Asisten AI
                                <span class="rounded-full bg-bps-orange px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider">Baru</span>
                            </strong>
                            Cukup ketik kebutuhan Anda, AI membantu mengisi formulir peminjaman dan keranjang persediaan.
                        </p>
                    </li>
                </ul>

                {{-- Contoh percakapan AI --}}
                <div class="mt-auto space-y-2 text-xs" aria-hidden="true">
                    <p class="ml-auto w-fit max-w-[80%] rounded-2xl rounded-br-sm bg-white px-3.5 py-2 font-medium text-bps-blue-dark shadow-md">Pinjam ruang rapat besok jam 9–11 untuk rapat tim</p>
                    <p class="flex w-fit max-w-[85%] items-start gap-2 rounded-2xl rounded-bl-sm bg-white/10 px-3.5 py-2 text-white/90 ring-1 ring-white/15">
                        @include('partials.asisten-ai.ikon', ['class' => 'mt-0.5 h-3.5 w-3.5 shrink-0 text-bps-orange-light'])
                        <span>Formulir peminjaman sudah diisi. Silakan periksa lalu kirim.</span>
                    </p>
                </div>
            </div>
        </section>

        {{-- ─── PANEL KANAN: MASUK ─── --}}
        <section class="flex flex-col justify-center gap-7 p-6 sm:p-10">
            {{-- Identitas (layar kecil saja, karena panel kiri disembunyikan) --}}
            <div class="flex items-center gap-3 lg:hidden">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-bps-blue to-bps-blue-light shadow-md">
                    <svg class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M3 3h4v8H3zm6-4h4v12H9zm6 2h4v10h-4zm-14 15h20v2H1z" />
                    </svg>
                </div>
                <div class="leading-tight">
                    <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-bps-orange">BPS Provinsi Jawa Timur</p>
                    <p class="text-sm font-semibold text-bps-blue-dark">Layanan Fasilitas &amp; Persediaan</p>
                </div>
            </div>

            <div class="space-y-1.5">
                <h2 class="text-2xl font-extrabold tracking-tight text-bps-blue-dark">Selamat Datang 👋</h2>
                <p class="text-sm text-slate-500">Masuk menggunakan akun Majapahit Anda untuk mulai meminjam dan mengajukan.</p>
            </div>

            {{-- Alert Error jika Login Gagal --}}
            @if($errors->any())
                <div class="bg-red-50 border-l-4 border-red-500 p-3 rounded-r-xl text-xs text-red-600 font-medium space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <p>⚠️ {{ $error }}</p>
                    @endforeach
                </div>
            @endif

            {{-- Login lewat Majapahit (SSO) --}}
            <a href="{{ url('/login/majapahit') }}"
                class="group w-full py-3.5 px-4 bg-gradient-to-r from-[#4a3c90] to-[#2970d6] hover:brightness-110 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#3956b3]/50 focus-visible:ring-offset-2 text-white text-base rounded-xl shadow-lg shadow-[#3956b3]/30 hover:shadow-xl hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-center gap-3">
                <span class="w-9 h-9 rounded-full bg-white flex items-center justify-center shrink-0 shadow-sm">
                    <img src="{{ asset('images/majapahit.png') }}" alt="" class="w-7 h-7 object-contain">
                </span>
                <span>Masuk dengan <strong class="font-bold">Majapahit</strong></span>
                <svg class="h-4 w-4 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5-5 5M6 12h12" />
                </svg>
            </a>

            {{-- Akses tanpa masuk --}}
            <div class="space-y-3">
                <div class="flex items-center gap-3 text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                    <span class="h-px flex-1 bg-slate-200"></span>
                    Lihat tanpa masuk
                    <span class="h-px flex-1 bg-slate-200"></span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <a href="{{ route('peminjaman.index') }}"
                        class="group flex items-center gap-3 rounded-xl border border-slate-200 p-3 transition hover:border-bps-blue/40 hover:bg-bps-blue/5 hover:shadow-sm">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-bps-blue/10 text-bps-blue">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </span>
                        <span class="text-xs text-slate-500"><strong class="block text-sm text-slate-800 group-hover:text-bps-blue">Jadwal Fasilitas</strong>Lihat peminjaman</span>
                    </a>
                    <a href="{{ route('barang.katalog') }}"
                        class="group flex items-center gap-3 rounded-xl border border-slate-200 p-3 transition hover:border-bps-orange/40 hover:bg-bps-orange/5 hover:shadow-sm">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-bps-orange/10 text-bps-orange">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                        </span>
                        <span class="text-xs text-slate-500"><strong class="block text-sm text-slate-800 group-hover:text-bps-orange">Katalog Persediaan</strong>Lihat stok tersedia</span>
                    </a>
                </div>
            </div>

            <p class="text-center text-[11px] text-slate-400">© {{ now()->year }} Badan Pusat Statistik Provinsi Jawa Timur</p>
        </section>
    </main>

</body>
</html>
