<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Customer;
use App\Http\Controllers\BarangController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

// ─── 🔑 HALAMAN UTAMA / LOGIN ──────────────────────────────────
Route::get('/', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ─── 🚗 PEMINJAMAN FASILITAS (MOBIL / RUANG / ZOOM) — SATU ROUTE UNTUK SEMUA PERAN ───
// Hak akses per peminjaman (milik sendiri, status, admin) dicek di BookingController
Route::prefix('peminjaman')->name('peminjaman.')->group(function () {
    // Kalender & daftar: bisa dilihat tanpa login (tamu tanpa aksi)
    Route::get('/', [BookingController::class, 'index'])->name('index');
    Route::get('/data', [BookingController::class, 'data'])->name('data');

    Route::middleware(['auth', 'role:admin|customer'])->group(function () {
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
// Admin: kelola barang & stok (+ katalog di /barang/katalog); customer: katalog (pilih barang ke keranjang)
Route::prefix('barang')->name('barang.')->middleware(['auth', 'role:admin|customer'])->group(function () {
    Route::get('/', [BarangController::class, 'index'])->name('index');
    Route::get('/data', [BarangController::class, 'data'])->name('data');
    Route::get('/katalog', [BarangController::class, 'katalog'])->name('katalog');

    Route::middleware('role:admin')->group(function () {
        Route::post('/', [BarangController::class, 'store'])->name('store');
        Route::post('/import', [BarangController::class, 'importExcel'])->name('import');
        Route::get('/import', [BarangController::class, 'riwayatImport'])->name('import.riwayat');
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
// Admin: semua pengajuan, setujui/tolak; customer: riwayat sendiri. Keduanya bisa mengirim isi keranjang. Hak milik dicek di OrderController
Route::prefix('pengajuan')->name('pengajuan.')->middleware(['auth', 'role:admin|customer'])->group(function () {
    Route::get('/', [OrderController::class, 'index'])->name('index');
    Route::get('/saya', [OrderController::class, 'saya'])->name('saya');
    Route::get('/{order}/cetak-pdf', [OrderController::class, 'cetakPdf'])->name('cetak-pdf');
    Route::post('/', [OrderController::class, 'store'])->name('store');
    Route::patch('/{order}/status', [OrderController::class, 'updateStatus'])->middleware('role:admin')->name('update-status');
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


// ─── 👨‍💼 ADMIN ROUTES ────────────────────────────────────────────────
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:admin'])
    ->group(function () {

        Route::get('/dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');


        // 👥 MANAJEMEN DATA PENGGUNA
        Route::prefix('manajemen-user')->name('manajemen-user.')->group(function () {
            Route::get('/', [Admin\ManajemenUserController::class, 'index'])->name('index');
            Route::post('/', [Admin\ManajemenUserController::class, 'store'])->name('store');
            Route::put('/{id}', [Admin\ManajemenUserController::class, 'update'])->name('update');
            Route::delete('/{user}', [Admin\ManajemenUserController::class, 'destroy'])->name('destroy');
        });

    });


// ─── 🧑‍💻 CUSTOMER ROUTES ───────────────────────────────────────────
Route::prefix('customer')
    ->name('customer.')
    ->middleware(['auth', 'role:customer'])
    ->group(function () {

        Route::get('/dashboard', [Customer\DashboardController::class, 'index'])->name('dashboard');

    });