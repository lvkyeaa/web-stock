<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Riwayat;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// Controller pengajuan barang untuk role admin (semua pengajuan, setujui/tolak, pengajuan sendiri langsung disetujui) maupun customer (pengajuan sendiri).
// Aksi khusus satu peran dibatasi di route; hak akses per pengajuan (milik sendiri) dicek di sini.
class OrderController extends Controller
{
    public function index(Request $request)
    {
        if ($request->user()->hasRole('admin')) {
            $transaksi = Order::with(['user', 'items'])->latest()->paginate(10);

            return view('admin.transaksi.index', compact('transaksi'));
        }

        return $this->saya($request);
    }

    // Pengajuan milik user yang login (customer: halaman pengajuan utamanya; admin: pengajuan yang ia buat sendiri)
    public function saya(Request $request)
    {
        $riwayat = $request->user()->orders()->with('items')->latest()->get();

        return view('customer.pengajuan.index', compact('riwayat'));
    }

    // Checkout: isi keranjang menjadi satu pengajuan (order).
    // Customer: pending, stok dipesan sampai admin menyetujui. Admin: langsung disetujui & stok langsung dipotong.
    public function store(Request $request)
    {
        $user = $request->user();
        $isAdmin = $user->hasRole('admin');

        $hasil = DB::transaction(function () use ($user, $isAdmin) {
            // Satu checkout per user dalam satu waktu: submit ganda menunggu di sini, lalu mendapati keranjang kosong
            User::whereKey($user->id)->lockForUpdate()->first();

            // Kunci baris barang di keranjang: checkout lain untuk barang yang sama menunggu di sini,
            // lalu menghitung stok tersedia setelah pengajuan ini tercatat (tidak bisa dua orang mendapat unit terakhir).
            // PENTING: semua pembacaan sebelum kunci barang didapat harus locking read (lockForUpdate). Di MySQL
            // (REPEATABLE READ) snapshot transaksi dibuat pada SELECT biasa pertama; jika itu terjadi sebelum menunggu kunci,
            // keranjang() di bawah tidak melihat pengajuan yang baru di-commit checkout lain, dan unit terakhir bisa diajukan dua kali.
            Barang::whereKey($user->cartItems()->lockForUpdate()->pluck('barang_id'))->lockForUpdate()->get();

            $keranjang = $user->keranjang();

            if ($keranjang->isEmpty()) {
                return 'Keranjang kamu masih kosong!';
            }

            // Pengajuan hanya dibuat jika semua barang muat di stok tersedia; keranjang tidak diubah
            $kurang = $keranjang->reject(fn (CartItem $item) => $item->cukup());
            if ($kurang->isNotEmpty()) {
                return 'Stok tidak mencukupi untuk: ' . $kurang->map(fn (CartItem $item) => "{$item->barang->nama_barang} (tersedia " . max(0, $item->barang->tersedia) . ')')->implode(', ')
                    . '. Kurangi jumlah atau hapus persediaannya, lalu kirim ulang.';
            }

            $order = $user->orders()->create([
                'code'   => Order::buatKode(),
                'status' => $isAdmin ? 'disetujui' : 'pending',
            ]);

            $order->items()->createMany($keranjang->map(fn (CartItem $item) => [
                'barang_id'   => $item->barang_id,
                'stock_id'    => $item->barang->stock_id,
                'nama_barang' => $item->barang->nama_barang,
                'satuan'      => $item->barang->satuan,
                'jumlah'      => $item->jumlah,
            ])->all());

            if ($isAdmin) {
                // Baris barang sudah dikunci di atas dan jumlahnya muat di stok tersedia, jadi stok tidak bisa minus
                foreach ($keranjang as $item) {
                    $item->barang->decrement('stock', $item->jumlah);
                }

                Riwayat::create([
                    'order_id'          => $order->id,
                    'actor_id'          => $user->id,
                    'status_sebelumnya' => null,
                    'status_sesudah'    => 'disetujui',
                    'catatan'           => 'Pengajuan admin, langsung disetujui.',
                ]);
            }

            $user->cartItems()->delete();

            return $order;
        });

        // Dipanggil dari halaman keranjang lewat fetch (JSON)
        if (is_string($hasil)) {
            // Stok tersedia terbaru tiap baris, agar peringatan di halaman keranjang langsung sesuai
            return response()->json([
                'message' => $hasil,
                'items'   => $user->keranjang()->map(fn (CartItem $item) => $item->untukKeranjang())->values(),
            ], 422);
        }

        $pesan = $isAdmin
            ? "Pengajuan {$hasil->code} disetujui dan stok telah dipotong."
            : "Pengajuan {$hasil->code} berhasil dikirim!";

        // Halaman keranjang lalu pindah ke Pengajuan Saya (url); pesan sukses tampil di sana lewat flash session
        session()->flash('success', $pesan);

        return response()->json([
            'message'          => $pesan,
            'code'             => $hasil->code,
            'url'              => route($isAdmin ? 'pengajuan.saya' : 'pengajuan.index'),
            'jumlah_keranjang' => 0,
        ]);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:disetujui,ditolak',
            'alasan' => 'nullable|string|max:500',
        ]);

        $error = DB::transaction(function () use ($request, $order) {
            // Kunci order agar dua admin tidak memproses pengajuan yang sama bersamaan
            $order = Order::whereKey($order->id)->lockForUpdate()->first();

            if ($order->status !== 'pending') {
                return 'Transaksi ini sudah diproses sebelumnya.';
            }

            if ($request->status === 'disetujui') {
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

            Riwayat::create([
                'order_id'          => $order->id,
                'actor_id'          => auth()->id(),
                'status_sebelumnya' => $order->status,
                'status_sesudah'    => $request->status,
                'catatan'           => $request->alasan,
            ]);

            $order->update([
                'status' => $request->status,
                'alasan' => $request->alasan,
            ]);

            return null;
        });

        if ($error) {
            return back()->with('error', $error);
        }

        return back()->with('success', $request->status === 'disetujui'
            ? 'Permintaan persediaan berhasil disetujui dan stok telah dipotong.'
            : 'Permintaan persediaan telah ditolak.');
    }

    public function cetakPdf(Request $request, Order $order)
    {
        // Customer hanya boleh mencetak miliknya; 404 agar keberadaan pengajuan milik user lain tidak terungkap
        abort_unless($request->user()->hasRole('admin') || $order->user_id === $request->user()->id, 404);

        if ($order->status !== 'disetujui') {
            return back()->with('error', 'Cetak dokumen hanya tersedia untuk transaksi yang telah disetujui (ACC).');
        }

        $order->load(['user', 'items']);

        return Pdf::loadView('pdf.bukti-pengajuan', ['pengajuan' => $order, 'nomorSurat' => $order->nomorSurat()])
            ->setPaper('a4', 'portrait')
            ->stream('Permintaan_ATK_' . $order->code . '.pdf');
    }
}
