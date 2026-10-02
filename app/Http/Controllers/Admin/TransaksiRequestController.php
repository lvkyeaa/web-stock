<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Riwayat;
use App\Models\TransaksiRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class TransaksiRequestController extends Controller
{
    public function index()
    {
        // Ambil data transaksi beserta relasi pemohon dan detail barang
        $transaksi = TransaksiRequest::with(['user', 'details.barang'])->latest()->paginate(10);
        return view('admin.transaksi.index', compact('transaksi'));
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:disetujui,ditolak',
            'alasan' => 'nullable|string|max:500',
        ]);

        $transaksi = TransaksiRequest::with('details.barang')->findOrFail($id);

        // Mencegah pemrosesan ganda jika status sudah bukan pending
        if ($transaksi->status !== 'pending') {
            return back()->with('error', 'Transaksi ini sudah diproses sebelumnya.');
        }

        $statusSebelumnya = $transaksi->status;

        // Validasi Ketersediaan Stok sebelum memproses persetujuan
        if ($request->status === 'disetujui') {
            foreach ($transaksi->details as $detail) {
                if ($detail->barang && $detail->barang->stock < $detail->jumlah_diminta) {
                    return back()->with('error', "Stok barang '{$detail->barang->nama_barang}' tidak mencukupi. Sisa stok: {$detail->barang->stock}, diminta: {$detail->jumlah_diminta}.");
                }
            }
        }

        // Jalankan Database Transaction
        DB::transaction(function () use ($request, $transaksi, $statusSebelumnya) {
            
            // 1. Update status transaksi induk
            $transaksi->update([
                'status' => $request->status,
                'alasan' => $request->alasan,
            ]);

            // 2. Terapkan logika pada item detail
            if ($request->status === 'disetujui') {
                foreach ($transaksi->details as $detail) {
                    $detail->update([
                        'jumlah_disetujui' => $detail->jumlah_diminta,
                        'status_item'      => 'Disetujui'
                    ]);

                    // Kurangi stok barang di database
                    if ($detail->barang) {
                        $detail->barang->decrement('stock', $detail->jumlah_diminta);
                    }
                }
            } else {
                // Jika ditolak
                foreach ($transaksi->details as $detail) {
                    $detail->update([
                        'jumlah_disetujui' => 0,
                        'status_item'      => 'Ditolak'
                    ]);
                }
            }

            // 3. Catat ke tabel Riwayat
            Riwayat::create([
                'transaksi_request_id' => $transaksi->id,
                'actor_id'             => auth()->id(),
                'status_sebelumnya'    => $statusSebelumnya,
                'status_sesudah'       => $request->status,
                'catatan'              => $request->alasan,
            ]);
        });

        // Response eksplisit setelah transaksi DB selesai
        $pesan = $request->status === 'disetujui'
            ? 'Permintaan barang berhasil disetujui dan stok telah dipotong.'
            : 'Permintaan barang telah ditolak.';

        return back()->with('success', $pesan);
    }

    public function cetakPdf($id)
{
    // Ambil data transaksi
    $pengajuan = \App\Models\TransaksiRequest::with(['user', 'details.barang'])
        ->findOrFail($id);

    // Validasi: Hanya pengajuan yang statusnya 'disetujui' yang boleh dicetak PDF
    if ($pengajuan->status !== 'disetujui') {
        return back()->with('error', 'Cetak dokumen hanya tersedia untuk transaksi yang telah disetujui (ACC).');
    }

    // 💡 Hitung urutan HANYA transaksi yang 'disetujui' di bulan & tahun yang sama
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