<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// facility_request_id = fasilitas yang diminta customer
// facility_id         = fasilitas yang diberikan admin (kosong sampai disetujui)
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('peminjamans', function (Blueprint $table) {
            $table->uuid('facility_request_id')->nullable()->after('user_id');
        });

        // Data lama: fasilitas yang tercatat dianggap sebagai fasilitas yang diminta
        DB::table('peminjamans')->update(['facility_request_id' => DB::raw('facility_id')]);

        Schema::table('peminjamans', function (Blueprint $table) {
            $table->uuid('facility_request_id')->nullable(false)->change();
            $table->foreign('facility_request_id')->references('id')->on('facilities')->cascadeOnDelete();

            $table->dropForeign(['facility_id']);
        });

        Schema::table('peminjamans', function (Blueprint $table) {
            $table->uuid('facility_id')->nullable()->change();
            $table->foreign('facility_id')->references('id')->on('facilities')->nullOnDelete();
        });

        // Belum / tidak disetujui berarti belum ada fasilitas yang diberikan
        DB::table('peminjamans')->where('status', '!=', 'disetujui')->update(['facility_id' => null]);
    }

    public function down(): void
    {
        DB::table('peminjamans')->whereNull('facility_id')->update(['facility_id' => DB::raw('facility_request_id')]);

        Schema::table('peminjamans', function (Blueprint $table) {
            $table->dropForeign(['facility_id']);
        });

        Schema::table('peminjamans', function (Blueprint $table) {
            $table->uuid('facility_id')->nullable(false)->change();
            $table->foreign('facility_id')->references('id')->on('facilities')->cascadeOnDelete();

            $table->dropForeign(['facility_request_id']);
            $table->dropColumn('facility_request_id');
        });
    }
};
