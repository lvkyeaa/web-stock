<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $password = config('app.first_admin_password');

        if (blank($password)) {
            throw new RuntimeException('FIRST_ADMIN_PASSWORD belum diisi di .env');
        }

        // Aman dijalankan ulang: id sama dengan UserSeeder, password hanya di-set saat akun pertama kali dibuat
        $user = User::firstOrNew(['id' => '019edef7-799e-706e-8a3b-d77143182225']);

        if (! $user->exists) {
            $user->password = bcrypt($password);
        }

        $user->name = 'Admin Persediaan';
        $user->username = 'admin@mail.com';
        $user->email = 'admin@mail.com';
        $user->save();

        if (! $user->roles()->exists()) {
            $user->assignRole('admin');
        }
    }
}
