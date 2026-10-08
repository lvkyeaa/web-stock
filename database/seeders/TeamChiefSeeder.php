<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

class TeamChiefSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // nama tim => username (= email) ketua tim (satu pegawai boleh menjadi ketua di beberapa tim)
        $chiefs = [
            'Tim Umum'                                  => 'umum@mail.com',
            'Pelayanan dan HUMAS'                       => 'yanmas@mail.com',
            'Pengembangan Jaringan dan Pengolahan Data' => 'ipj@mail.com',
            'Statistik Sosial'                          => 'sosial@mail.com',
            'Statistik Produksi'                        => 'produksi@mail.com',
            'Neraca Wilayah dan Analisis'               => 'nerwilis@mail.com',
            'SINGASARI'                                 => 'sosial@mail.com',
            'Distribusi dan Pelaksana SE2026'           => 'distribusi@mail.com',
            'PSS dan EPSS'                              => 'pss@mail.com',
        ];

        $users = User::pluck('id', 'username');

        foreach ($chiefs as $team => $username) {
            // syncWithoutDetaching: aman dijalankan ulang tanpa membuat data ganda
            Team::where('name', $team)->firstOrFail()->chiefs()->syncWithoutDetaching([$users[$username]]);
        }
    }
}
