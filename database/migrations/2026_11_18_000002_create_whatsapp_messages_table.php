<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Log pesan WhatsApp pengajuan: satu baris per penerima per kejadian.
// terkirim = API Majapahit menerima pesan (2xx), bukan bukti pesan sampai/dibaca di HP penerima.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete(); // penerima; log tidak menghalangi hapus user
            $table->string('phone', 20)->nullable(); // nomor saat dikirim; kosong jika dilewati
            $table->enum('event', ['diajukan', 'disetujui_ketua', 'ditolak_ketua', 'keputusan_admin']);
            $table->text('message');
            $table->enum('status', ['menunggu', 'terkirim', 'gagal', 'dilewati'])->default('menunggu');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->unsignedSmallInteger('response_code')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};
