<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Persetujuan dua tingkat: ketua tim (penanggung jawab) lalu admin. Status menjadi enum;
// pengajuan lama yang masih 'pending' belum melewati ketua, jadi langsung menunggu admin.
// Tanda tangan pemohon disimpan sebagai path file di disk local (privat).
return new class extends Migration
{
    public function up(): void
    {
        DB::table('orders')->where('status', 'pending')->update(['status' => 'menunggu_admin']);

        Schema::table('orders', function (Blueprint $table) {
            $table->enum('status', ['menunggu_ketua', 'menunggu_admin', 'disetujui', 'ditolak', 'dibatalkan'])
                ->default('menunggu_ketua')->change();
            $table->string('user_signature')->nullable()->after('alasan');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('user_signature');
            $table->string('status')->default('pending')->change();
        });

        DB::table('orders')->whereIn('status', ['menunggu_ketua', 'menunggu_admin'])->update(['status' => 'pending']);
    }
};
