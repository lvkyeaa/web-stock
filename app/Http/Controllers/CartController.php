<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\CartItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    // Kerangka halaman + data awal; ubah jumlah, hapus, dan kirim pengajuan lewat fetch (JSON) tanpa reload
    public function index(Request $request)
    {
        $items = $request->user()->keranjang()->map(fn (CartItem $item) => $item->untukKeranjang())->values();

        return view('keranjang.index', compact('items'));
    }

    // Dipanggil dari katalog lewat fetch (JSON)
    public function store(Request $request, Barang $barang)
    {
        $request->validate(['jumlah' => 'required|integer|min:1']);

        $user = $request->user();

        // Kunci baris user: dua penambahan barang yang sama bersamaan (klik ganda, dua tab) berjalan bergantian,
        // jadi tidak bentrok di unique(user_id, barang_id) dan jumlahnya tidak saling menimpa
        return DB::transaction(function () use ($request, $user, $barang) {
            User::whereKey($user->id)->lockForUpdate()->first();

            $barang = Barang::withDipesan()->findOrFail($barang->id);
            $item = $user->cartItems()->firstOrNew(['barang_id' => $barang->id]);
            $jumlah = (int) $item->jumlah + (int) $request->jumlah;

            if ($jumlah > $barang->tersedia) {
                return response()->json([
                    'message' => "Stok '{$barang->nama_barang}' tidak mencukupi. Tersedia: " . max(0, $barang->tersedia) . " {$barang->satuan}, karena di keranjang sudah ada " . (int) $item->jumlah . '.',
                ], 422);
            }

            $item->fill(['jumlah' => $jumlah])->save();

            return response()->json([
                'message'          => "'{$barang->nama_barang}' ditambahkan ke keranjang ({$jumlah} {$barang->satuan}).",
                'jumlah_keranjang' => $user->jumlahKeranjang(),
            ]);
        });
    }

    public function update(Request $request, CartItem $cartItem)
    {
        abort_unless($cartItem->user_id === $request->user()->id, 403);

        $request->validate(['jumlah' => 'required|integer|min:1']);

        $cartItem->setRelation('barang', $barang = Barang::withDipesan()->findOrFail($cartItem->barang_id));

        // Ditolak: jumlah lama tetap; stok tersedia terbaru ikut dikirim agar baris di halaman ikut diperbarui
        if ($request->jumlah > $barang->tersedia) {
            return response()->json([
                'message' => "Stok '{$barang->nama_barang}' tidak mencukupi. Tersedia: " . max(0, $barang->tersedia) . " {$barang->satuan}.",
                'item'    => $cartItem->untukKeranjang(),
            ], 422);
        }

        $cartItem->update(['jumlah' => $request->jumlah]);

        return response()->json([
            'message' => 'Jumlah persediaan berhasil diperbarui!',
            'item'    => $cartItem->untukKeranjang(),
        ]);
    }

    public function destroy(Request $request, CartItem $cartItem)
    {
        abort_unless($cartItem->user_id === $request->user()->id, 403);

        $cartItem->delete();

        return response()->json([
            'message'          => 'Persediaan dihapus dari keranjang.',
            'jumlah_keranjang' => $request->user()->jumlahKeranjang(),
        ]);
    }
}
