<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Nomor surat (PDF) disimpan saat pengajuan disetujui, tidak lagi dihitung ulang setiap kali dicetak
// (sebelumnya nomor bisa berubah setelah dicetak, dan dua pengajuan bisa mendapat nomor yang sama)
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->char('periode_nomor', 7)->nullable()->after('alasan'); // YYYY-MM (bulan pengajuan dibuat)
            $table->unsignedInteger('nomor_urut')->nullable()->after('periode_nomor');
            $table->unique(['periode_nomor', 'nomor_urut']);
        });

        // Pengajuan yang sudah disetujui diberi nomor sesuai aturan lama (urutan dibuat dalam bulan yang sama),
        // jadi dokumen yang sudah dicetak tetap bernomor sama. Waktu dibuat yang sama diurutkan berdasarkan id.
        $urutan = [];
        $disetujui = DB::table('orders')->where('status', 'disetujui')->orderBy('created_at')->orderBy('id')->get(['id', 'created_at']);
        foreach ($disetujui as $order) {
            $periode = substr((string) $order->created_at, 0, 7);
            $urutan[$periode] = ($urutan[$periode] ?? 0) + 1;

            DB::table('orders')->where('id', $order->id)->update(['periode_nomor' => $periode, 'nomor_urut' => $urutan[$periode]]);
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['periode_nomor', 'nomor_urut']);
            $table->dropColumn(['periode_nomor', 'nomor_urut']);
        });
    }
};
