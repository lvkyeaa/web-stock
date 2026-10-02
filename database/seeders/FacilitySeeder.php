<?php

namespace Database\Seeders;

use App\Models\Facility;
use App\Models\FacilityType;
use Illuminate\Database\Seeder;

class FacilitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Data awal: daftar fasilitas yang sebelumnya di-hardcode di controller
        $facilities = [
            'car'  => ['L 38', 'L 1760 HP', 'L 1758 HP', 'L 1759 HP', 'B 1877 PQS', 'B 1875 PQS', 'S 3351 NP', 'S 3346 NP'],
            'room' => ['Ruang Vicon', 'Ruang Aula Majapahit'],
            'zoom' => ['Zoom Kantor'],
        ];

        // Jenis fasilitas dibuat oleh FacilityTypeSeeder
        $types = FacilityType::pluck('id', 'code');

        foreach ($facilities as $code => $names) {
            foreach ($names as $name) {
                // firstOrCreate: aman dijalankan ulang tanpa membuat data ganda
                Facility::firstOrCreate(['facility_type_id' => $types[$code], 'name' => $name]);
            }
        }
    }
}
