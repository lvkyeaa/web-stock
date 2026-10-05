<?php

namespace Database\Seeders;

use App\Models\Barang;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        // Ambil contoh 1 user dan 1 barang dummy yang sudah dibuat seeder sebelumnya
        $user = User::first();
        $barang = Barang::first();

        if ($user && $barang) {
            $order = $user->orders()->create(['code' => Order::buatKode()]);

            $order->items()->create([
                'barang_id'   => $barang->id,
                'stock_id'    => $barang->stock_id,
                'nama_barang' => $barang->nama_barang,
                'satuan'      => $barang->satuan,
                'jumlah'      => 3,
            ]);
        }
    }
}
