<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

// Mengganti kolom nama_item + jenis_fasilitas dengan relasi facility_id
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('peminjamans', function (Blueprint $table) {
            $table->uuid('facility_id')->nullable()->after('user_id');
        });

        // Hubungkan data lama ke fasilitas berdasarkan nama & jenis (buat fasilitas jika belum ada)
        $types = ['mobil' => 'car', 'ruang' => 'room'];

        $existing = DB::table('peminjamans')->select('nama_item', 'jenis_fasilitas')->distinct()->get();
        foreach ($existing as $row) {
            $type = $types[$row->jenis_fasilitas];
            $facilityId = DB::table('facilities')->where('type', $type)->where('name', $row->nama_item)->value('id');

            if (!$facilityId) {
                $facilityId = (string) Str::orderedUuid();
                DB::table('facilities')->insert([
                    'id' => $facilityId,
                    'type' => $type,
                    'name' => $row->nama_item,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('peminjamans')
                ->where('nama_item', $row->nama_item)
                ->where('jenis_fasilitas', $row->jenis_fasilitas)
                ->update(['facility_id' => $facilityId]);
        }

        Schema::table('peminjamans', function (Blueprint $table) {
            $table->uuid('facility_id')->nullable(false)->change();
            $table->foreign('facility_id')->references('id')->on('facilities')->cascadeOnDelete();
            $table->dropColumn(['nama_item', 'jenis_fasilitas']);
        });
    }

    public function down(): void
    {
        Schema::table('peminjamans', function (Blueprint $table) {
            $table->string('nama_item')->nullable()->after('user_id');
            $table->enum('jenis_fasilitas', ['mobil', 'ruang'])->nullable()->after('nama_item');
        });

        $types = ['car' => 'mobil', 'room' => 'ruang'];

        foreach (DB::table('facilities')->get() as $facility) {
            DB::table('peminjamans')->where('facility_id', $facility->id)->update([
                'nama_item' => $facility->name,
                'jenis_fasilitas' => $types[$facility->type],
            ]);
        }

        Schema::table('peminjamans', function (Blueprint $table) {
            $table->dropForeign(['facility_id']);
            $table->dropColumn('facility_id');
        });
    }
};
