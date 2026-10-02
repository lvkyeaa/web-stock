<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facility_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique(); // contoh: car, room (dipakai di URL filter)
            $table->string('name');           // contoh: Mobil Dinas
            $table->string('icon');           // emoji, contoh: 🚗
            $table->string('color', 7);       // warna hex kalender, contoh: #0284c7
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_types');
    }
};
