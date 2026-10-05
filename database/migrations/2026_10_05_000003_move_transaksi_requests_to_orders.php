<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

// Memindahkan data transaksi_requests (+ details) ke orders (+ order_items), riwayat ikut menunjuk ke orders.
// ID transaksi lama dipakai ulang sebagai ID order, jadi riwayat cukup ganti nama kolom.
return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('transaksi_requests')->get() as $transaksi) {
            DB::table('orders')->insert([
                'id'         => $transaksi->id,
                'user_id'    => $transaksi->user_id,
                'code'       => 'REQ-' . date('Ymd', strtotime($transaksi->created_at)) . '-' . Str::upper(Str::random(6)),
                'status'     => $transaksi->status,
                'alasan'     => $transaksi->alasan,
                'created_at' => $transaksi->created_at,
                'updated_at' => $transaksi->updated_at,
            ]);
        }

        $details = DB::table('transaksi_request_details')
            ->join('barang', 'barang.id', '=', 'transaksi_request_details.barang_id')
            ->select('transaksi_request_details.*', 'barang.stock_id', 'barang.nama_barang', 'barang.satuan')
            ->get();

        foreach ($details as $detail) {
            DB::table('order_items')->insert([
                'id'          => (string) Str::uuid(),
                'order_id'    => $detail->transaksi_request_id,
                'barang_id'   => $detail->barang_id,
                'stock_id'    => $detail->stock_id,
                'nama_barang' => $detail->nama_barang,
                'satuan'      => $detail->satuan,
                'jumlah'      => $detail->jumlah_diminta,
                'created_at'  => $detail->created_at,
                'updated_at'  => $detail->updated_at,
            ]);
        }

        Schema::table('riwayat', function (Blueprint $table) {
            $table->dropForeign(['transaksi_request_id']);
            $table->renameColumn('transaksi_request_id', 'order_id');
        });
        Schema::table('riwayat', function (Blueprint $table) {
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
        });

        Schema::drop('transaksi_request_details');
        Schema::drop('transaksi_requests');
    }

    public function down(): void
    {
        Schema::create('transaksi_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->string('status')->default('pending');
            $table->string('alasan')->nullable();
            $table->timestamps();
        });

        Schema::create('transaksi_request_details', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('transaksi_request_id')->constrained('transaksi_requests')->cascadeOnDelete();
            $table->uuid('barang_id');
            $table->foreign('barang_id')->references('id')->on('barang')->cascadeOnDelete();
            $table->integer('jumlah_diminta');
            $table->integer('jumlah_disetujui')->nullable();
            $table->string('status_item')->default('Pending');
            $table->timestamps();
        });

        foreach (DB::table('orders')->get() as $order) {
            DB::table('transaksi_requests')->insert([
                'id'         => $order->id,
                'user_id'    => $order->user_id,
                'status'     => $order->status,
                'alasan'     => $order->alasan,
                'created_at' => $order->created_at,
                'updated_at' => $order->updated_at,
            ]);
        }

        // Item yang barangnya sudah dihapus tidak bisa dikembalikan (kolom barang_id lama wajib diisi)
        foreach (DB::table('order_items')->join('orders', 'orders.id', '=', 'order_items.order_id')->whereNotNull('barang_id')->select('order_items.*', 'orders.status')->get() as $item) {
            DB::table('transaksi_request_details')->insert([
                'transaksi_request_id' => $item->order_id,
                'barang_id'            => $item->barang_id,
                'jumlah_diminta'       => $item->jumlah,
                'jumlah_disetujui'     => $item->status === 'disetujui' ? $item->jumlah : ($item->status === 'ditolak' ? 0 : null),
                'status_item'          => ucfirst($item->status),
                'created_at'           => $item->created_at,
                'updated_at'           => $item->updated_at,
            ]);
        }

        Schema::table('riwayat', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->renameColumn('order_id', 'transaksi_request_id');
        });
        Schema::table('riwayat', function (Blueprint $table) {
            $table->foreign('transaksi_request_id')->references('id')->on('transaksi_requests')->onDelete('cascade');
        });

        DB::table('order_items')->delete();
        DB::table('orders')->delete();
    }
};
