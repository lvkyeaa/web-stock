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

<body class="bg-bps-cream-bg font-[Plus_Jakarta_Sans] min-h-screen flex items-center justify-center p-4">

    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl border border-slate-200/80 p-8 space-y-6">
        
        {{-- Logo dan Judul --}}
        <div class="text-center space-y-2">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-bps-blue to-bps-orange mx-auto flex items-center justify-center shadow-md">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <h2 class="text-xl font-extrabold text-bps-blue-dark tracking-tight">Selamat Datang</h2>
            <p class="text-xs text-slate-400">Masuk menggunakan akun Majapahit Anda</p>
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
            class="w-full py-3.5 px-4 bg-gradient-to-r from-[#4a3c90] to-[#2970d6] hover:brightness-110 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#3956b3]/50 focus-visible:ring-offset-2 text-white text-base rounded-xl shadow-lg shadow-[#3956b3]/30 hover:shadow-xl transition-all duration-200 flex items-center justify-center gap-3">
            <span class="w-9 h-9 rounded-full bg-white flex items-center justify-center shrink-0 shadow-sm">
                <img src="{{ asset('images/majapahit.png') }}" alt="" class="w-7 h-7 object-contain">
            </span>
            <span>Masuk dengan <strong class="font-bold">Majapahit</strong></span>
        </a>

        {{-- 💡 TOMBOL PORTAL MONITORING YANG DISEMBUNYIKAN DI SINI --}}
        <div class="text-center pt-4 border-t border-slate-100 flex flex-col gap-2">
            <p class="text-[11px] text-slate-400 font-medium">Lihat tanpa masuk:</p>
            <a href="{{ route('peminjaman.index') }}" class="w-full py-2.5 border border-slate-200 hover:border-bps-orange hover:bg-bps-orange/5 text-slate-600 hover:text-bps-orange text-xs font-bold rounded-xl transition-all duration-150 flex items-center justify-center gap-2 shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                Lihat Jadwal Fasilitas
            </a>
            <a href="{{ route('barang.katalog') }}" class="w-full py-2.5 border border-slate-200 hover:border-bps-orange hover:bg-bps-orange/5 text-slate-600 hover:text-bps-orange text-xs font-bold rounded-xl transition-all duration-150 flex items-center justify-center gap-2 shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                Lihat Katalog Persediaan
            </a>
        </div>

    </div>

</body>
</html>