<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\TransaksiRequest;
use App\Models\TransaksiRequestDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf; // 💡 Import Facade PDF untuk fitur cetak PDF

class PengajuanController extends Controller
{
    // 1. TAMPILKAN ISI KERANJANG & RIWAYAT PENGAJUAN
    public function index()
    {
        // 1. Ambil data keranjang session
        $keranjang = session()->get('keranjang', []);
        
        $userId = Auth::id();
        
        // 2. Ambil data riwayat berdasarkan ID user yang benar
        $riwayat = TransaksiRequest::with('details.barang')
                    ->where('user_id', $userId)
                    ->latest()
                    ->get();
        
        // 3. Lempar semuanya ke view pengajuan/index
        return view('customer.pengajuan.index', compact('keranjang', 'riwayat'));
    }

    // Jika di web.php memanggil fungsi history() secara terpisah
    public function history()
    {
        $userId = Auth::id();

        $riwayat = TransaksiRequest::with('details.barang')
                    ->where('user_id', $userId)
                    ->latest()
                    ->get();

        return view('customer.pengajuan.index', compact('riwayat'));
    }

    // 2. TAMBAH BARANG KE KERANJANG (SESSION)
    public function store(Request $request, $barang_id)
    {
        $barang = Barang::findOrFail($barang_id);
        $keranjang = session()->get('keranjang', []);

        // Jika barang sudah ada di keranjang, tambahkan jumlahnya
        if (isset($keranjang[$barang_id])) {
            $keranjang[$barang_id]['jumlah'] += $request->input('jumlah', 1);
        } else {
            // Jika belum ada, masukkan data baru
            $keranjang[$barang_id] = [
                'id' => $barang->id,
                'nama_barang' => $barang->nama_barang,
                'satuan' => $barang->satuan,
                'jumlah' => $request->input('jumlah', 1)
            ];
        }

        session()->put('keranjang', $keranjang);

        return redirect()->back()->with('success', 'Barang berhasil ditambahkan ke keranjang!');
    }

    // 3. UPDATE JUMLAH BARANG DI KERANJANG
    public function update(Request $request, $id)
    {
        $keranjang = session()->get('keranjang', []);

        if (isset($keranjang[$id])) {
            $keranjang[$id]['jumlah'] = $request->jumlah;
            session()->put('keranjang', $keranjang);
            return redirect()->back()->with('success', 'Jumlah barang berhasil diperbarui!');
        }

        return redirect()->back()->with('error', 'Barang tidak ditemukan di keranjang.');
    }

    // 4. HAPUS BARANG DARI KERANJANG
    public function destroy($id)
    {
        $keranjang = session()->get('keranjang', []);

        if (isset($keranjang[$id])) {
            unset($keranjang[$id]);
            session()->put('keranjang', $keranjang);
            return redirect()->back()->with('success', 'Barang dihapus dari keranjang.');
        }

        return redirect()->back()->with('error', 'Barang tidak ditemukan.');
    }

    // 5. PROSES CHECKOUT (PINDAH SESSION KE DATABASE)
    public function checkout()
    {
        $keranjang = session()->get('keranjang', []);

        if (empty($keranjang)) {
            return redirect()->back()->with('error', 'Keranjang kamu masih kosong!');
        }

        $userId = Auth::id();

        try {
            DB::transaction(function () use ($keranjang, $userId) {
                // 1. Buat data induk transaksi pengajuan
                $transaksi = TransaksiRequest::create([
                    'user_id' => $userId, 
                    'status' => 'pending',
                    'alasan' => null
                ]);

                // 2. Loop & masukkan semua item keranjang ke tabel detail
                foreach ($keranjang as $item) {
                    TransaksiRequestDetail::create([
                        'transaksi_request_id' => $transaksi->id,
                        'barang_id' => $item['id'],
                        'jumlah_diminta' => $item['jumlah'],
                        'jumlah_disetujui' => null,
                        'status_item' => 'Pending'
                    ]);
                }
            });

            // Kosongkan keranjang setelah berhasil disimpan ke DB
            session()->forget('keranjang');

            // Kembali ke halaman pengajuan indeks dengan tab riwayat aktif
            return redirect()->route('customer.pengajuan.index', ['tab' => 'riwayat'])
                ->with('success', 'Pengajuan kelompok barang berhasil dikirim!');

        } catch (\Exception $e) {
            dd("Gagal menyimpan ke database!", $e->getMessage());
        }
    }

    // 💡 6. MENGHASILKAN/CETAK PDF BUKTI PENGAJUAN (OPSIONAL)
    public function cetakPdf($id)
{
    // Ambil data transaksi khusus milik customer yang sedang login
    $userId = auth()->id();
    
    $pengajuan = \App\Models\TransaksiRequest::with(['user', 'details.barang'])
        ->where('user_id', $userId)
        ->findOrFail($id);

    // Validasi: Hanya pengajuan yang statusnya 'disetujui' yang boleh dicetak PDF
    if ($pengajuan->status !== 'disetujui') {
        return back()->with('error', 'Cetak dokumen hanya tersedia untuk transaksi yang telah disetujui (ACC).');
    }

    // 💡 Hitung urutan HANYA transaksi yang 'disetujui' secara global di bulan & tahun yang sama
    $nomorUrut = \App\Models\TransaksiRequest::where('status', 'disetujui')
        ->whereYear('created_at', $pengajuan->created_at->year)
        ->whereMonth('created_at', $pengajuan->created_at->month)
        ->where('created_at', '<', $pengajuan->created_at)
        ->count() + 1; // Ditambah 1 agar urutan pertama bernilai 1

    // Mapping Angka Bulan ke Romawi
    $mapRomawi = [
        1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
        7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'
    ];
    $bulanRomawi = $mapRomawi[(int)$pengajuan->created_at->format('n')];

    // Ambil username / nama tim
    $namaTim = strtolower($pengajuan->user->username ?? 'umum');

    // Format Nomor Surat: 1/umum/VIII/2026
    $nomorSurat = sprintf("%d/%s/%s/%d", $nomorUrut, $namaTim, $bulanRomawi, $pengajuan->created_at->year);

    // Render template PDF
    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.bukti-pengajuan', compact('pengajuan', 'nomorSurat'))
              ->setPaper('a4', 'portrait');

    return $pdf->stream('Permintaan_ATK_' . sprintf('%02d', $nomorUrut) . '.pdf');
}
}