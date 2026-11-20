<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\AIController;
use App\Http\Controllers\Customer;
use App\Http\Controllers\BarangController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PersetujuanCepatController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\MajapahitController;
use Illuminate\Support\Facades\Route;

// ─── 🔑 HALAMAN UTAMA / LOGIN ──────────────────────────────────
Route::get('/', [AuthController::class, 'showLogin'])->name('login');
Route::get('/webadmin', [AuthController::class, 'showWebAdminLogin'])->name('login.webadmin'); // form username & password (tidak ditautkan dari halaman login)
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Keycloak routes
Route::get('/login/majapahit', [MajapahitController::class, 'redirectToMajapahit']);
Route::get('/majapahit-auth', [MajapahitController::class, 'handleMajapahitCallback']);

// ─── 🚗 PEMINJAMAN FASILITAS (MOBIL / RUANG / ZOOM) — SATU ROUTE UNTUK SEMUA PERAN ───
// Hak akses per peminjaman (milik sendiri, status, admin) dicek di BookingController
Route::prefix('peminjaman')->name('peminjaman.')->group(function () {
    // Kalender & daftar: bisa dilihat tanpa login (tamu tanpa aksi)
    Route::get('/', [BookingController::class, 'index'])->name('index');
    Route::get('/data', [BookingController::class, 'data'])->name('data');

    Route::middleware(['auth', 'role:admin|customer'])->group(function () {
        Route::get('/buat', [BookingController::class, 'create'])->name('create');
        Route::post('/', [BookingController::class, 'store'])->name('store');
        Route::put('/{id}', [BookingController::class, 'update'])->name('update');
        Route::delete('/{id}', [BookingController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/status', [BookingController::class, 'updateStatus'])->middleware('role:admin')->name('update-status');
    });
});

// URL lama (tautan notifikasi tersimpan, bookmark) diarahkan ke halaman peminjaman, query string tetap
foreach (['monitoring', 'admin/peminjaman', 'customer/peminjaman'] as $urlLama) {
    Route::get($urlLama, fn () => redirect()->route('peminjaman.index', request()->query()));
}

// ─── 📦 BARANG & STOK — SATU ROUTE UNTUK SEMUA PERAN ───
// Katalog & daftar persediaan bisa dilihat tanpa login (tamu: tanpa keranjang, tombol tambah diganti tombol masuk)
Route::prefix('barang')->name('barang.')->group(function () {
    Route::get('/data', [BarangController::class, 'data'])->name('data');
    Route::get('/katalog', [BarangController::class, 'katalog'])->name('katalog');
});

// Admin: kelola barang & stok (+ katalog di /barang/katalog); customer: katalog (pilih barang ke keranjang)
Route::prefix('barang')->name('barang.')->middleware(['auth', 'role:admin|customer'])->group(function () {
    Route::get('/', [BarangController::class, 'index'])->name('index');

    Route::middleware('role:admin')->group(function () {
        Route::post('/', [BarangController::class, 'store'])->name('store');
        Route::post('/import', [BarangController::class, 'importExcel'])->name('import');
        Route::get('/import', [BarangController::class, 'riwayatImport'])->name('import.riwayat');
        Route::get('/import/template', [BarangController::class, 'templateImport'])->name('import.template');
        Route::put('/{barang}', [BarangController::class, 'update'])->name('update');
        Route::patch('/{barang}/stock', [BarangController::class, 'updateStock'])->name('update-stock');
        Route::delete('/{barang}', [BarangController::class, 'destroy'])->name('destroy');
    });
});

// URL lama halaman barang admin & katalog customer, query string (pencarian, halaman) tetap
foreach (['admin/barang', 'customer/katalog'] as $urlLama) {
    Route::get($urlLama, fn () => redirect()->route('barang.index', request()->query()));
}

// ─── 📝 PENGAJUAN BARANG — SATU ROUTE UNTUK SEMUA PERAN ───
// Ketua tim yang dipilih: setujui/tolak tingkat 1; admin: semua pengajuan, setujui/tolak tingkat 2; customer: riwayat sendiri. Admin & customer bisa mengirim isi keranjang. Hak milik dicek di OrderController
Route::prefix('pengajuan')->name('pengajuan.')->middleware(['auth', 'role:admin|customer'])->group(function () {
    Route::get('/', [OrderController::class, 'index'])->name('index');
    Route::get('/saya', [OrderController::class, 'saya'])->name('saya');
    Route::get('/ketua', [OrderController::class, 'ketua'])->name('ketua'); // persetujuan tingkat 1: pengajuan yang memilih user ini sebagai ketua tim
    Route::get('/data', [OrderController::class, 'data'])->name('data');
    Route::get('/{order}/cetak-pdf', [OrderController::class, 'cetakPdf'])->name('cetak-pdf');
    Route::get('/{order}/tanda-tangan/{jenis}', [OrderController::class, 'tandaTangan'])->whereIn('jenis', ['pemohon', 'ketua'])->name('tanda-tangan');
    Route::post('/', [OrderController::class, 'store'])->name('store');
    Route::patch('/{order}/status', [OrderController::class, 'updateStatus'])->middleware('role:admin')->name('update-status');
    Route::patch('/{order}/keputusan-ketua', [OrderController::class, 'keputusanKetua'])->name('keputusan-ketua'); // hanya ketua tim yang dipilih (dicek di controller)
    Route::patch('/{order}/batal', [OrderController::class, 'batal'])->name('batal'); // pemohon (selama menunggu) / admin (selama menunggu ketua tim) membatalkan pengajuan
});

// ─── ⚡ PERSETUJUAN CEPAT TANPA LOGIN: tautan bertanda tangan (pengajuan + penerima) dari pesan WhatsApp ───
// Tanpa auth; keabsahan tautan dicek middleware 'signed', wewenang & status pengajuan dicek di PersetujuanCepatController
Route::prefix('persetujuan-cepat')->name('persetujuan-cepat.')->middleware(['signed', 'throttle:30,1'])->group(function () {
    Route::get('/{order}/{user}', [PersetujuanCepatController::class, 'show'])->name('show');
    Route::post('/{order}/{user}', [PersetujuanCepatController::class, 'store'])->name('store');
});

// ─── 🛒 KERANJANG BARANG (admin & customer), hak milik dicek di CartController; kirim pengajuan: pengajuan.store ───
Route::prefix('keranjang')->name('keranjang.')->middleware(['auth', 'role:admin|customer'])->group(function () {
    Route::get('/', [CartController::class, 'index'])->name('index');
    Route::post('/add/{barang}', [CartController::class, 'store'])->name('add');
    Route::patch('/update/{cartItem}', [CartController::class, 'update'])->name('update');
    Route::delete('/delete/{cartItem}', [CartController::class, 'destroy'])->name('delete');
});

// URL lama halaman transaksi admin & riwayat pengajuan customer
foreach (['admin/transaksi', 'customer/pengajuan', 'customer/riwayat-pengajuan'] as $urlLama) {
    Route::get($urlLama, fn () => redirect()->route('pengajuan.index', request()->query()));
}
Route::get('customer/keranjang', fn () => redirect()->route('keranjang.index'));

// ─── 👤 PROFIL: data akun sendiri, ubah nomor WhatsApp (admin & customer) ───
Route::prefix('profil')->name('profil.')->middleware(['auth', 'role:admin|customer'])->group(function () {
    Route::get('/', [ProfilController::class, 'index'])->name('index');
    Route::put('/', [ProfilController::class, 'update'])->name('update');
});

// ─── 🤖 ASISTEN AI: kalimat → formulir peminjaman / isi keranjang (dashboard, halaman peminjaman, katalog) ───
Route::prefix('asisten-ai')->name('asisten-ai.')->middleware(['auth', 'role:admin|customer'])->group(function () {
    Route::post('/', [AIController::class, 'process'])->middleware('throttle:10,1')->name('process');
    Route::get('/persediaan', [AIController::class, 'supplies'])->name('supplies');
    Route::post('/persediaan', [AIController::class, 'apply'])->name('apply');
});


// ─── 👨‍💼 ADMIN ROUTES ────────────────────────────────────────────────
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:admin'])
    ->group(function () {

        Route::get('/dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');


        // 👥 MANAJEMEN DATA PENGGUNA
        Route::prefix('manajemen-user')->name('manajemen-user.')->group(function () {
            Route::get('/', [Admin\ManajemenUserController::class, 'index'])->name('index');
            Route::get('/data', [Admin\ManajemenUserController::class, 'data'])->name('data');

            // Ketua tim/penanggung jawab: halaman + API JSON (daftar, tambah, hapus)
            Route::prefix('ketua-tim')->name('ketua-tim.')->group(function () {
                Route::get('/', [Admin\TeamChiefController::class, 'index'])->name('index');
                Route::get('/data', [Admin\TeamChiefController::class, 'data'])->name('data');
                Route::post('/', [Admin\TeamChiefController::class, 'store'])->name('store');
                Route::delete('/{teamChief}', [Admin\TeamChiefController::class, 'destroy'])->name('destroy');
            });

            Route::post('/', [Admin\ManajemenUserController::class, 'store'])->name('store');
            Route::put('/{id}', [Admin\ManajemenUserController::class, 'update'])->name('update');
            Route::delete('/{user}', [Admin\ManajemenUserController::class, 'destroy'])->name('destroy');
        });

        // 🏢 KELOLA FASILITAS (mobil, ruang rapat, Zoom): halaman + API JSON
        Route::prefix('fasilitas')->name('fasilitas.')->group(function () {
            Route::get('/', [Admin\FacilityController::class, 'index'])->name('index');
            Route::get('/data', [Admin\FacilityController::class, 'data'])->name('data');
            Route::post('/', [Admin\FacilityController::class, 'store'])->name('store');
            Route::put('/{facility}', [Admin\FacilityController::class, 'update'])->name('update');
            Route::delete('/{facility}', [Admin\FacilityController::class, 'destroy'])->name('destroy');
        });

    });


// ─── 🧑‍💻 CUSTOMER ROUTES ───────────────────────────────────────────
Route::prefix('customer')
    ->name('customer.')
    ->middleware(['auth', 'role:customer'])
    ->group(function () {

        Route::get('/dashboard', [Customer\DashboardController::class, 'index'])->name('dashboard');

    });