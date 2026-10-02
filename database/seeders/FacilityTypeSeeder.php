<?php

namespace Database\Seeders;

use App\Models\FacilityType;
use Illuminate\Database\Seeder;

class FacilityTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ['code' => 'car', 'name' => 'Mobil Dinas', 'icon' => '🚗', 'color' => '#0284c7'],
            ['code' => 'room', 'name' => 'Ruang Rapat', 'icon' => '🏢', 'color' => '#d97706'],
            ['code' => 'zoom', 'name' => 'Zoom Meeting', 'icon' => '💻', 'color' => '#7c3aed'],
        ];

        foreach ($types as $type) {
            // updateOrCreate: aman dijalankan ulang tanpa membuat data ganda
            FacilityType::updateOrCreate(['code' => $type['code']], $type);
        }
    }
}
