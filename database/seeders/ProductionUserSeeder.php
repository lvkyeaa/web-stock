<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ProductionUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $path = database_path('data/user_production.csv');

        if (! file_exists($path)) {
            $this->command?->warn("File {$path} tidak ditemukan, seeder dilewati.");

            return;
        }

        $file = fopen($path, 'r');
        fgetcsv($file); // lewati header: name,email

        while (($row = fgetcsv($file)) !== false) {
            $name = Str::squish($row[0] ?? '');
            $email = Str::lower(trim($row[1] ?? ''));

            // Lewati baris kosong & user yang sudah ada (dicari lewat email)
            if ($email === '' || User::where('email', $email)->exists()) {
                continue;
            }

            // Sama seperti login pertama via Majapahit: password acak, login lewat SSO
            $user = User::create([
                'name' => $name,
                'username' => $email,
                'email' => $email,
                'password' => Hash::make(Str::random(40)),
            ]);
            $user->assignRole('customer');
        }

        fclose($file);
    }
}
