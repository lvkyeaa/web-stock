<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk Admin — BPS Provinsi Jawa Timur</title>
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
            <p class="text-xs text-slate-400">Masuk menggunakan username / email dan kata sandi</p>
        </div>

        {{-- Alert Error jika Login Gagal --}}
        @if($errors->any())
            <div class="bg-red-50 border-l-4 border-red-500 p-3 rounded-r-xl text-xs text-red-600 font-medium space-y-0.5">
                @foreach ($errors->all() as $error)
                    <p>⚠️ {{ $error }}</p>
                @endforeach
            </div>
        @endif

        {{-- Form Login --}}
        <form action="{{ route('login.post') }}" method="POST" class="space-y-4">
            @csrf
            
            <div>
                <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Username atau Email</label>
                <input type="text" name="username" value="{{ old('username') }}" required autofocus
                    class="w-full px-4 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-bps-blue/20 focus:border-bps-blue transition duration-200" 
                    placeholder="Contoh: admin / user@bps.go.id">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Kata Sandi</label>
                <input type="password" name="password" required
                    class="w-full px-4 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-bps-blue/20 focus:border-bps-blue transition duration-200" 
                    placeholder="••••••••">
            </div>

            <button type="submit" 
                class="w-full py-3 bg-gradient-to-r from-bps-blue to-bps-blue-dark hover:from-bps-blue-dark hover:to-bps-blue text-white text-xs font-bold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 tracking-wider uppercase mt-2">
                Masuk Sekarang
            </button>
        </form>

    </div>

</body>
</html>