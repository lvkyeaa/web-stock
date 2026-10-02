<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

// Mengganti kolom enum facilities.type dengan relasi facility_type_id
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->uuid('facility_type_id')->nullable()->after('id');
        });

        // Konversi data lama: buat jenis fasilitas untuk setiap nilai enum yang sudah dipakai
        $defaults = [
            'car'  => ['name' => 'Mobil Dinas', 'icon' => '🚗', 'color' => '#0284c7'],
            'room' => ['name' => 'Ruang Rapat', 'icon' => '🏢', 'color' => '#d97706'],
        ];

        foreach (DB::table('facilities')->distinct()->pluck('type') as $code) {
            $typeId = DB::table('facility_types')->where('code', $code)->value('id');

            if (!$typeId) {
                $typeId = (string) Str::orderedUuid();
                DB::table('facility_types')->insert([
                    'id' => $typeId,
                    'code' => $code,
                    'name' => $defaults[$code]['name'],
                    'icon' => $defaults[$code]['icon'],
                    'color' => $defaults[$code]['color'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('facilities')->where('type', $code)->update(['facility_type_id' => $typeId]);
        }

        Schema::table('facilities', function (Blueprint $table) {
            $table->uuid('facility_type_id')->nullable(false)->change();
            $table->foreign('facility_type_id')->references('id')->on('facility_types');
            $table->dropColumn('type');
        });
    }

    public function down(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->enum('type', ['car', 'room'])->nullable()->after('id');
        });

        foreach (DB::table('facility_types')->whereIn('code', ['car', 'room'])->get() as $type) {
            DB::table('facilities')->where('facility_type_id', $type->id)->update(['type' => $type->code]);
        }

        Schema::table('facilities', function (Blueprint $table) {
            $table->dropForeign(['facility_type_id']);
            $table->dropColumn('facility_type_id');
        });
    }
};
