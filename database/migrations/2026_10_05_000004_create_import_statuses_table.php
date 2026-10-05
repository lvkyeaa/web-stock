<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Status impor Excel persediaan (diproses lewat queue), dan penanda barang yang dibuat oleh impor
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_statuses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete(); // admin yang mengunggah
            $table->string('nama_file');                  // nama file asli dari pengguna
            $table->string('path');                       // lokasi file di disk 'local'
            $table->string('status')->default('start');   // start | processing | error | success
            $table->text('keterangan')->nullable();       // alasan gagal, atau ringkasan hasil jika berhasil
            $table->timestamps();
        });

        // Hanya diisi untuk barang yang DIBUAT oleh impor; impor yang sekadar menambah stok tidak mengubahnya
        Schema::table('barang', function (Blueprint $table) {
            $table->foreignUuid('import_status_id')->nullable()->after('foto')->constrained('import_statuses')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('barang', function (Blueprint $table) {
            $table->dropConstrainedForeignId('import_status_id');
        });

        Schema::dropIfExists('import_statuses');
    }
};
