<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Keputusan tiap tingkat persetujuan pengajuan (satu baris per tingkat): siapa, setuju/tolak, kapan.
// Tanda tangan hanya untuk ketua tim (path file di disk local); admin tidak menandatangani.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_approvals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained('orders')->cascadeOnDelete();
            $table->enum('level', ['ketua', 'admin']);
            $table->foreignUuid('user_id')->constrained('users')->restrictOnDelete(); // pengguna dengan riwayat tidak boleh terhapus
            $table->enum('keputusan', ['disetujui', 'ditolak']);
            $table->string('signature_path')->nullable();
            $table->string('catatan')->nullable(); // alasan penolakan
            $table->timestamps();

            $table->unique(['order_id', 'level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_approvals');
    }
};
