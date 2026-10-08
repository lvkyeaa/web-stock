<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            ['id' => '019edef7-799e-706e-8a3b-d77143182225', 'email' => 'admin@mail.com', 'name' => 'Admin Persediaan', 'password' => 'password', 'role' => 'admin'],
            ['id' => '019edef7-8c70-72a9-8b00-7036b7e71536', 'email' => 'umum@mail.com', 'name' => 'Hadi Suroso'],
            ['id' => '019edef7-8466-7350-b62f-278a1e009fcd', 'email' => 'yanmas@mail.com', 'name' => 'Peni Meivita'],
            ['id' => '019edef7-7db9-70eb-96d0-b17979abba11', 'email' => 'ipj@mail.com', 'name' => 'Amin Sani Kertiyasa'],
            ['id' => '019edef7-7b11-73c9-8ad2-28d965624729', 'email' => 'sosial@mail.com', 'name' => 'Nanang Widaryoko'],
            ['id' => '019edef7-7c67-71fe-990e-bd7609fac0bf', 'email' => 'produksi@mail.com', 'name' => 'Adenan'],
            ['id' => '019edef7-8062-72ab-a906-3954f9391e07', 'email' => 'nerwilis@mail.com', 'name' => 'Nurul Andriana'],
            ['id' => '019edef7-7f0d-7254-9c24-13c85fd860db', 'email' => 'distribusi@mail.com', 'name' => 'Ike Rahayu Sri'],
            ['id' => '019edef7-8316-727b-88ea-06fd750becf4', 'email' => 'pss@mail.com', 'name' => 'Debora Sulistya Rini'],
        ];

        foreach ($users as $data) {
            // Aman dijalankan ulang: user yang sudah ada (dicari lewat id) hanya diperbarui nama, username & email-nya (password & role tidak di-reset)
            $user = User::firstOrNew(['id' => $data['id']]);

            if (! $user->exists) {
                $user->password = bcrypt($data['password'] ?? 'password123');
            }

            $user->name = $data['name'];
            $user->username = $data['email']; // username disamakan dengan email
            $user->email = $data['email'];
            $user->save();

            // Role Spatie (role admin & customer dibuat oleh migration)
            if (! $user->roles()->exists()) {
                $user->assignRole($data['role'] ?? 'customer');
            }
        }
    }
}
