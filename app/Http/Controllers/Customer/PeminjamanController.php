<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Peminjaman;
use Illuminate\Support\Facades\Log;
use App\Notifications\PermintaanPeminjamanBaru; // 💡 Sudah ter-import
use App\Models\User; // 💡 Sudah ter-import

class PeminjamanController extends Controller
{
    public function index($jenis = null)
    {
        // Mengambil ID User dari session custom BPS (Mencoba array, jika gagal mencoba objek)
        $userId = is_array(session('auth_user')) ? session('auth_user.id') : (session('auth_user')->id ?? null);

        // 1. Ambil data peminjaman milik user yang login
        $query = Peminjaman::where('user_id', $userId);
        if ($jenis && in_array($jenis, ['mobil', 'ruang'])) {
            $query->where('jenis_fasilitas', $jenis);
        }
        $peminjaman = $query->latest()->get();

        // 2. Ambil data MOBIL yang disetujui untuk kalender
        $dataMobil = Peminjaman::where('status', 'disetujui')
            ->where('jenis_fasilitas', 'mobil')
            ->get()
            ->map(function ($item) {
                return [
                    'title' => '🚗 ' . $item->nama_item,
                    'start' => $item->waktu_mulai->toIso8601String(),
                    'end'   => $item->waktu_selesai->toIso8601String(),
                    'color' => '#0284c7',
                    'extendedProps' => [
                        'jenis' => 'mobil',
                        'keperluan' => $item->keperluan
                    ]
                ];
            });

        // 3. Ambil data RUANG yang disetujui untuk kalender
        $dataRuang = Peminjaman::where('status', 'disetujui')
            ->where('jenis_fasilitas', 'ruang')
            ->get()
            ->map(function ($item) {
                return [
                    'title' => '🏢 ' . $item->nama_item,
                    'start' => $item->waktu_mulai->toIso8601String(),
                    'end'   => $item->waktu_selesai->toIso8601String(),
                    'color' => '#d97706',
                    'extendedProps' => [
                        'jenis' => 'ruang',
                        'keperluan' => $item->keperluan
                    ]
                ];
            });

        $peminjamanDisetujui = array_merge($dataMobil->toArray(), $dataRuang->toArray());

        return view('customer.peminjaman.index', compact('peminjaman', 'peminjamanDisetujui', 'dataMobil', 'dataRuang', 'jenis'));
    }

    public function store(Request $request)
    {
        // Perbaikan Validasi: Mengubah after:now menjadi after_or_equal:today agar toleran terhadap timezone
        $request->validate([
            'nama_item' => 'required|string|max:255',
            'jenis_fasilitas' => 'required|in:mobil,ruang',
            'waktu_mulai' => 'required|date|after_or_equal:today',
            'waktu_selesai' => 'required|date|after:waktu_mulai',
            'keperluan' => 'nullable|string',
        ]);

        // Cek Bentrok Jadwal
        // Cek Bentrok Jadwal
        $bentrok = Peminjaman::where('nama_item', $request->nama_item)
        ->where('jenis_fasilitas', $request->jenis_fasilitas)
        ->where('status', 'disetujui')
        ->where(function ($query) use ($request) {
            $query->whereBetween('waktu_mulai', [$request->waktu_mulai, $request->waktu_selesai])
                    ->orWhereBetween('waktu_selesai', [$request->waktu_mulai, $request->waktu_selesai]) // 💡 FIX: Ubah 'walesai' jadi 'waktu_selesai'
                    ->orWhere(function ($q) use ($request) {
                    $q->where('waktu_mulai', '<=', $request->waktu_mulai)
                        ->where('waktu_selesai', '>=', $request->waktu_selesai);
                    });
            })->exists();

        if ($bentrok) {
            return redirect()->back()->with('error', 'Jadwal penugasan/peminjaman untuk item tersebut sudah terisi pada waktu terpilih.');
        }

        // Ambil ID User dengan aman (antisipasi bentuk array atau objek session)
        $userId = is_array(session('auth_user')) ? session('auth_user.id') : (session('auth_user')->id ?? null);

        if (!$userId) {
            return redirect()->back()->with('error', 'Gagal menyimpan: Sesi login Anda tidak valid atau telah berakhir.');
        }

        try {
            // 1. Jalankan proses simpan ke Database dan tampung ke variabel
            $peminjamanBaru = Peminjaman::create([
                'user_id' => $userId,
                'nama_item' => $request->nama_item,
                'jenis_fasilitas' => $request->jenis_fasilitas,
                'waktu_mulai' => $request->waktu_mulai,
                'waktu_selesai' => $request->waktu_selesai,
                'keperluan' => $request->keperluan,
                'status' => 'pending',
            ]);

            // 💡 2. PICU NOTIFIKASI KE ADMIN
            // Mencari seluruh user yang rolenya adalah admin
            $admins = User::where('role', 'admin')->get();
            
            foreach ($admins as $admin) {
                $admin->notify(new PermintaanPeminjamanBaru($peminjamanBaru));
            }

            return redirect()->back()->with('success', 'Pengajuan peminjaman berhasil dikirim!');

        } catch (\Exception $e) {
            // Jika ada eror database, erornya akan dilempar ke layar biar ketahuan
            return redirect()->back()->with('error', 'Terjadi kesalahan database: ' . $e->getMessage());
        }
    }
}