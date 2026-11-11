// Service worker minimal agar aplikasi bisa dipasang (PWA). Tidak menyimpan cache:
// stok & pengajuan harus selalu terbaru, jadi semua permintaan langsung ke jaringan.
self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (event) => event.waitUntil(self.clients.claim()));
self.addEventListener('fetch', () => {});
