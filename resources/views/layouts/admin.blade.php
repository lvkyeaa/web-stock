<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') — BPS Provinsi Jawa Timur</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{-- 💡 AlpineJS untuk interaksi dropdown notifikasi --}}
    <script defer src="{{ asset('vendor/alpinejs-3.17.4.min.js') }}"></script>
</head>

<body class="bg-bps-cream-bg font-[Plus_Jakarta_Sans] flex h-dvh overflow-hidden"
    x-data="{ sidebarOpen: false, sidebarCollapsed: false }" @keydown.escape.window="sidebarOpen = false">

    {{-- SIDEBAR --}}
    @include('layouts.partials.sidebar')

    {{-- Latar gelap saat sidebar terbuka di layar kecil --}}
    <div x-show="sidebarOpen" x-cloak x-transition.opacity @click="sidebarOpen = false"
        class="fixed inset-0 z-30 bg-slate-900/40 lg:hidden"></div>

    {{-- MAIN CONTENT --}}
    <div class="flex-1 min-w-0 flex flex-col overflow-hidden">
        {{-- NAVBAR (relative z-20: dropdown notifikasi & menu akun tampil di atas konten halaman; backdrop-blur membuat lapisan sendiri) --}}
        @include('layouts.partials.topbar')

        {{-- CONTENT --}}
        <main class="flex-1 overflow-y-auto p-4 sm:p-6">
            @if (session('success'))
                <div
                    class="mb-4 p-4 rounded-xl bg-green-50 border border-green-200 text-green-800 text-sm flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd" />
                    </svg>
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div
                    class="mb-4 p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm flex items-center gap-2">
                    <svg class="w-5 h-5 text-red-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd" />
                    </svg>
                    {{ session('error') }}
                </div>
            @endif
            @if (session('warning'))
                <div role="alert"
                    class="mb-4 p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-sm flex items-center gap-2">
                    <svg class="w-5 h-5 text-amber-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd" />
                    </svg>
                    {{ session('warning') }}
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    @include('layouts.partials.dialog-script')
</body>

</html>