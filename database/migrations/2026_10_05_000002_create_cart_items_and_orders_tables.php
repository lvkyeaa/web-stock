<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Keranjang disimpan di database (bukan session), pengajuan barang disimpan sebagai orders + order_items
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('barang_id')->constrained('barang')->cascadeOnDelete();
            $table->unsignedInteger('jumlah');
            $table->timestamps();

            $table->unique(['user_id', 'barang_id']);
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('code')->unique(); // contoh: REQ-20261005-AB12CD
            $table->string('status')->default('pending'); // pending | disetujui | ditolak
            $table->string('alasan')->nullable();
            $table->timestamps();
        });

        // stock_id (kode barang), nama_barang & satuan disalin saat checkout, jadi riwayat tetap utuh walau barang diubah/dihapus
        Schema::create('order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignUuid('barang_id')->nullable()->constrained('barang')->nullOnDelete();
            $table->string('stock_id')->nullable();
            $table->string('nama_barang');
            $table->string('satuan');
            $table->unsignedInteger('jumlah');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('cart_items');
    }
};
