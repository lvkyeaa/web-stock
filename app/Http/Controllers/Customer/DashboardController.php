<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Peminjaman; // 💡 Memanggil model Peminjaman yang ada di folder Models kamu

class DashboardController extends Controller
{
    public function index()
    {
        $userId = auth()->id();
        
        // 1. Statistik Request Barang (Bawaan Lama)
        $stats = [
            'total'     => Order::where('user_id', $userId)->count(),
            'pending'   => Order::where('user_id', $userId)->where('status', 'pending')->count(),
            'disetujui' => Order::where('user_id', $userId)->where('status', 'disetujui')->count(),
            'ditolak'   => Order::where('user_id', $userId)->where('status', 'ditolak')->count(),
        ];

        // 2. Statistik Peminjaman Fasilitas (Mobil & Ruang Baru)
        $peminjamanStats = [
            'total'     => Peminjaman::where('user_id', $userId)->count(),
            'pending'   => Peminjaman::where('user_id', $userId)->where('status', 'pending')->count(),
            'disetujui' => Peminjaman::where('user_id', $userId)->where('status', 'disetujui')->count(),
            'ditolak'   => Peminjaman::where('user_id', $userId)->where('status', 'ditolak')->count(),
        ];

        // Mengirimkan data $stats (barang) dan $peminjamanStats (mobil/ruang) ke view dashboard
        return view('customer.dashboard', compact('stats', 'peminjamanStats'));
    }
}