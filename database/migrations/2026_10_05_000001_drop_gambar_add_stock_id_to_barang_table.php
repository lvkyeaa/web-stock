<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Kolom gambar dan foto duplikat: data gambar dipindah ke foto (yang dipakai kode), lalu gambar dihapus
return new class extends Migration
{
    public function up(): void
    {
        DB::table('barang')->whereNull('foto')->update(['foto' => DB::raw('gambar')]);

        Schema::table('barang', function (Blueprint $table) {
            $table->dropColumn('gambar');
            $table->string('stock_id')->nullable()->unique()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('barang', function (Blueprint $table) {
            $table->dropUnique(['stock_id']);
            $table->dropColumn('stock_id');
            $table->string('gambar')->nullable()->after('stock');
        });

        DB::table('barang')->update(['gambar' => DB::raw('foto')]);
    }
};
