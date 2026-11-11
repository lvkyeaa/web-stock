{{-- Aplikasi bisa dipasang (PWA): manifest, ikon, service worker. Tawaran pasang dari browser disimpan
     di window.tawaranPasang lalu dipakai tombol "Pasang Aplikasi" di top bar (layouts.partials.tombol-pasang) --}}
<link rel="manifest" href="/manifest.json">
<meta name="theme-color" content="#003d82">
<link rel="apple-touch-icon" href="/images/apple-touch-icon.png">
<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
    }
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        window.tawaranPasang = e;
        window.dispatchEvent(new Event('pasang-tersedia'));
    });
    window.addEventListener('appinstalled', () => {
        window.tawaranPasang = null;
        window.dispatchEvent(new Event('pasang-tersedia'));
    });
</script>
