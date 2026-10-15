<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Detail tambahan saat meminjam ruang rapat; untuk jenis fasilitas lain dibiarkan kosong
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('peminjamans', function (Blueprint $table) {
            $table->unsignedSmallInteger('jumlah_peserta')->nullable()->after('keperluan');
            $table->boolean('butuh_sound_system')->default(false)->after('jumlah_peserta');
            $table->boolean('butuh_konsumsi')->default(false)->after('butuh_sound_system');
        });
    }

    public function down(): void
    {
        Schema::table('peminjamans', function (Blueprint $table) {
            $table->dropColumn(['jumlah_peserta', 'butuh_sound_system', 'butuh_konsumsi']);
        });
    }
};
