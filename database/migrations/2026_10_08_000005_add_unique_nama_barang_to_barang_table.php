<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Nama persediaan unik. Collation utf8mb4_unicode_ci: beda huruf besar/kecil & spasi di akhir dianggap sama;
// spasi berlebih di tengah sudah dirapikan aplikasi (Barang::rapikanNama) sebelum disimpan
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('barang', function (Blueprint $table) {
            $table->unique('nama_barang');
        });
    }

    public function down(): void
    {
        Schema::table('barang', function (Blueprint $table) {
            $table->dropUnique(['nama_barang']);
        });
    }
};
