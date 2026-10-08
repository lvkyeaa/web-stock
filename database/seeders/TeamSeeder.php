<?php

namespace Database\Seeders;

use App\Models\Team;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // nama => nama singkat
        $teams = [
            'Tim Umum'                                  => 'Umum',
            'Pelayanan dan HUMAS'                       => 'Yanmas',
            'Pengembangan Jaringan dan Pengolahan Data' => 'IPJ',
            'Statistik Sosial'                          => 'Sosial',
            'Statistik Produksi'                        => 'Produksi',
            'Neraca Wilayah dan Analisis'               => 'Nerwilis',
            'SINGASARI'                                 => 'SINGASARI',
            'Distribusi dan Pelaksana SE2026'           => 'Distribusi',
            'PSS dan EPSS'                              => 'PSS',
        ];

        foreach ($teams as $name => $shortName) {
            // updateOrCreate: aman dijalankan ulang tanpa membuat data ganda
            Team::updateOrCreate(['name' => $name], ['short_name' => $shortName]);
        }
    }
}
