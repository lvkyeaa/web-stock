<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Pengguna yang punya riwayat (pengajuan, peminjaman, persetujuan, penanggung jawab) tidak boleh terhapus:
// sebelumnya CASCADE / SET NULL ikut menghapus atau mengosongkan riwayat tersebut. Sekarang database menolak (RESTRICT).
// Isi keranjang (cart_items) & ketua tim (team_chief) tetap CASCADE.
return new class extends Migration
{
    // [tabel, kolom, aturan hapus sebelumnya]
    private const KOLOM = [
        ['orders', 'user_id', 'cascade'],
        ['orders', 'person_responsible_user_id', 'set null'],
        ['peminjamans', 'user_id', 'cascade'],
        ['riwayat', 'actor_id', 'cascade'],
    ];

    public function up(): void
    {
        foreach (self::KOLOM as [$tabel, $kolom]) {
            $this->gantiAturanHapus($tabel, $kolom, 'restrict');
        }
    }

    public function down(): void
    {
        foreach (self::KOLOM as [$tabel, $kolom, $aturanLama]) {
            $this->gantiAturanHapus($tabel, $kolom, $aturanLama);
        }
    }

    private function gantiAturanHapus(string $tabel, string $kolom, string $aturan): void
    {
        Schema::table($tabel, function (Blueprint $table) use ($kolom, $aturan) {
            $table->dropForeign([$kolom]);
            $table->foreign($kolom)->references('id')->on('users')->onDelete($aturan);
        });
    }
};
