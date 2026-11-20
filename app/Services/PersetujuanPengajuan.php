<?php

namespace App\Services;

use App\Models\Barang;
use App\Models\Order;
use App\Models\Riwayat;
use App\Models\User;
use Illuminate\Support\Facades\DB;

// Keputusan persetujuan pengajuan (tingkat ketua tim & admin), dipakai halaman aplikasi dan persetujuan cepat dari WhatsApp.
// Hasil: null jika berhasil, atau pesan galat (string). Setelah berhasil, pihak terkait dikabari (lonceng + WhatsApp).
class PersetujuanPengajuan
{
    public function __construct(private KabarPengajuan $kabar)
    {
    }

    // Tingkat 1 oleh ketua tim yang dipilih. Setuju (wajib tanda tangan) → menunggu admin; tolak → selesai.
    public function ketua(Order $order, User $ketua, string $status, ?string $alasan, ?string $tandaTangan): ?string
    {
        $error = DB::transaction(function () use ($order, $ketua, $status, $alasan, $tandaTangan) {
            // Kunci order agar keputusan tidak bentrok dengan pembatalan oleh pemohon
            $order = Order::whereKey($order->id)->lockForUpdate()->first();

            if ($order->status !== 'menunggu_ketua') {
                return 'Pengajuan ini sudah diproses sebelumnya.';
            }

            $disetujui = $status === 'disetujui';
            $statusBaru = $disetujui ? 'menunggu_admin' : 'ditolak';

            $order->approvals()->create([
                'level'          => 'ketua',
                'user_id'        => $ketua->id,
                'keputusan'      => $status,
                'signature_path' => $disetujui ? Order::simpanTandaTangan($tandaTangan) : null,
                'catatan'        => $disetujui ? null : $alasan,
            ]);

            Riwayat::create([
                'order_id'          => $order->id,
                'actor_id'          => $ketua->id,
                'status_sebelumnya' => $order->status,
                'status_sesudah'    => $statusBaru,
                'catatan'           => $alasan,
            ]);

            $order->update(['status' => $statusBaru]);

            return null;
        });

        if (! $error) {
            $this->kabar->kirim($order->fresh(), $status === 'disetujui' ? 'disetujui_ketua' : 'ditolak_ketua', $alasan);
        }

        return $error;
    }

    // Tingkat 2 oleh admin (tanpa tanda tangan): setuju → stok dipotong & diberi nomor surat
    public function admin(Order $order, User $admin, string $status, ?string $alasan): ?string
    {
        $error = DB::transaction(function () use ($order, $admin, $status, $alasan) {
            // Kunci order agar dua admin tidak memproses pengajuan yang sama bersamaan
            $order = Order::whereKey($order->id)->lockForUpdate()->first();

            if ($order->status === 'menunggu_ketua') {
                return 'Pengajuan ini belum disetujui ketua tim.';
            }
            if ($order->status !== 'menunggu_admin') {
                return 'Transaksi ini sudah diproses sebelumnya.';
            }

            if ($status === 'disetujui') {
                // Kunci baris barang agar stok tidak bisa minus karena persetujuan bersamaan
                $barang = Barang::whereKey($order->items->pluck('barang_id'))->lockForUpdate()->get()->keyBy('id');

                foreach ($order->items as $item) {
                    $stok = $barang->get($item->barang_id);

                    if (! $stok) {
                        return "Persediaan '{$item->nama_barang}' sudah dihapus dari inventaris.";
                    }
                    if ($stok->stock < $item->jumlah) {
                        return "Stok persediaan '{$item->nama_barang}' tidak mencukupi. Sisa stok: {$stok->stock}, diminta: {$item->jumlah}.";
                    }
                }

                foreach ($order->items as $item) {
                    $barang->get($item->barang_id)->decrement('stock', $item->jumlah);
                }
            }

            $order->approvals()->create([
                'level'     => 'admin',
                'user_id'   => $admin->id,
                'keputusan' => $status,
                'catatan'   => $status === 'ditolak' ? $alasan : null,
            ]);

            Riwayat::create([
                'order_id'          => $order->id,
                'actor_id'          => $admin->id,
                'status_sebelumnya' => $order->status,
                'status_sesudah'    => $status,
                'catatan'           => $alasan,
            ]);

            $order->update(['status' => $status]);

            if ($status === 'disetujui') {
                $order->beriNomor(); // nomor surat PDF tetap sejak disetujui
            }

            return null;
        });

        if (! $error) {
            $this->kabar->kirim($order->fresh(), 'keputusan_admin', $alasan);
        }

        return $error;
    }
}
