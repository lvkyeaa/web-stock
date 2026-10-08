<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\Order;
use App\Models\Peminjaman;
use App\Models\Riwayat;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $hariIni = today();

        // Persediaan & pengajuan persediaan
        $stats = [
            'total_barang'        => Barang::count(),
            'stok_habis'          => Barang::count() - Barang::whereTersedia()->count(), // tidak ada stok yang bisa diajukan
            'pending'             => Order::where('status', 'pending')->count(),
            // Pengajuan yang disetujui bulan ini (waktu persetujuan dari riwayat)
            'disetujui_bulan_ini' => Riwayat::where('status_sesudah', 'disetujui')
                ->where('created_at', '>=', $hariIni->copy()->startOfMonth())
                ->distinct('order_id')
                ->count('order_id'),
            'total_users'         => User::count(),
        ];

        // Peminjaman fasilitas
        $peminjamanStats = [
            'pending'   => Peminjaman::where('status', 'pending')->count(),
            // Disetujui & berlangsung (sebagian) hari ini
            'hari_ini'  => Peminjaman::where('status', 'disetujui')
                ->where('waktu_mulai', '<', $hariIni->copy()->addDay())
                ->where('waktu_selesai', '>', $hariIni)
                ->count(),
            'disetujui' => Peminjaman::where('status', 'disetujui')->count(),
            'ditolak'   => Peminjaman::where('status', 'ditolak')->count(),
        ];

        return view('admin.dashboard', compact('stats', 'peminjamanStats'));
    }
}
