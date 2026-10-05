<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\Order;
use App\Models\User;
use App\Models\Peminjaman; // 💡 Memanggil model Peminjaman untuk merekap data fasilitas

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Statistik Inventaris Barang & User (Bawaan Lama Admin)
        $stats = [
            'total_barang'     => Barang::count(),
            'total_users'      => User::role('customer')->count(),
            'pending'          => Order::where('status', 'pending')->count(),
            'disetujui'        => Order::where('status', 'disetujui')->count(),
        ];

        // 2. 🆕 Statistik Peminjaman Fasilitas Masuk (Mobil & Ruang)
        $peminjamanStats = [
            'total'     => Peminjaman::count(),
            'pending'   => Peminjaman::where('status', 'pending')->count(),
            'disetujui' => Peminjaman::where('status', 'disetujui')->count(),
            'ditolak'   => Peminjaman::where('status', 'ditolak')->count(),
        ];

        // Mengirimkan variabel $stats dan $peminjamanStats ke view dashboard milik admin
        return view('admin.dashboard', compact('stats', 'peminjamanStats'));
    }
}