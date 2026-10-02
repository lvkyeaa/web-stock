<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Customer;
use App\Http\Controllers\BookingController;
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


// ─── 👨‍💼 ADMIN ROUTES ────────────────────────────────────────────────
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:admin'])
    ->group(function () {

        Route::get('/dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');

        // 📦 KELOMPOK ROUTE BARANG ADMIN
        Route::prefix('barang')->name('barang.')->group(function () {
            Route::get('/', [Admin\BarangController::class, 'index'])->name('index');
            Route::post('/', [Admin\BarangController::class, 'store'])->name('store');
            Route::put('/{barang}', [Admin\BarangController::class, 'update'])->name('update');
            Route::delete('/{barang}', [Admin\BarangController::class, 'destroy'])->name('destroy');
            Route::post('/import', [Admin\BarangController::class, 'importExcel'])->name('import');
        });

        // 📝 VERIFIKASI BATCH PER ITEM & UPDATE STATUS (SISI ADMIN)
        Route::prefix('transaksi')->name('transaksi.')->group(function () {
            Route::get('/', [Admin\TransaksiRequestController::class, 'index'])->name('index');
            Route::patch('/item/{detail_id}/{status}', [Admin\TransaksiRequestController::class, 'updateItemStatus'])->name('item.status');
            Route::get('/{id}/periksa', [Admin\TransaksiRequestController::class, 'review'])->name('review'); 
            Route::post('/{id}/simpan', [Admin\TransaksiRequestController::class, 'saveVerification'])->name('save'); 
            Route::patch('/{id}/status', [Admin\TransaksiRequestController::class, 'updateStatusViaModal'])->name('update-status-modal');
            Route::patch('/{transaksi}/massal', [Admin\TransaksiRequestController::class, 'updateStatus'])->name('update-status');
        });

        // 📄 ROUTE CETAK PDF SISI ADMIN
        Route::get('/pengajuan/{id}/cetak-pdf', [Admin\TransaksiRequestController::class, 'cetakPdf'])->name('pengajuan.cetak-pdf');

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

        Route::prefix('katalog')->name('katalog.')->group(function () {
            Route::get('/', [Customer\KatalogController::class, 'index'])->name('index');
        });

        // 💡 FIXED: Menambahkan route pengajuan.index agar sesuai dengan folder views/customer/pengajuan/index.blade.php
        Route::get('/pengajuan', [Customer\PengajuanController::class, 'index'])->name('pengajuan.index');

        // 🛒 SISTEM KERANJANG BELANJA BARANG
        Route::prefix('keranjang')->name('keranjang.')->group(function () {
            Route::get('/', [Customer\PengajuanController::class, 'index'])->name('index'); 
            Route::post('/add/{barang_id}', [Customer\PengajuanController::class, 'store'])->name('add'); 
            Route::patch('/update/{id}', [Customer\PengajuanController::class, 'update'])->name('update'); 
            Route::delete('/delete/{id}', [Customer\PengajuanController::class, 'destroy'])->name('delete'); 
            Route::post('/checkout', [Customer\PengajuanController::class, 'checkout'])->name('checkout'); 
        });

        Route::get('/riwayat-pengajuan', [Customer\PengajuanController::class, 'history'])->name('history');

        // 📄 ROUTE CETAK PDF SISI CUSTOMER
        Route::get('/pengajuan/{id}/cetak-pdf', [Customer\PengajuanController::class, 'cetakPdf'])->name('pengajuan.cetak-pdf');

    });