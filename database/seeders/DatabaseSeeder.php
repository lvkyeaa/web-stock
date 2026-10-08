<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            TeamSeeder::class,
            UserSeeder::class,
            TeamChiefSeeder::class,
            FacilityTypeSeeder::class,
            FacilitySeeder::class,
            BookingSeeder::class,
            BarangSeeder::class,
            RiwayatSeeder::class,
        ]);
    }
}
