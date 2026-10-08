<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Tim yang dipilih saat checkout (dipakai di nomor surat PDF); nullable karena pengajuan lama belum memilikinya
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignUuid('team_id')->nullable()->after('user_id')->constrained('teams')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('team_id');
        });
    }
};
