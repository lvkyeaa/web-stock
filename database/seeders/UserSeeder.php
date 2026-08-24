<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash; // Tambahkan namespace ini

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\User::insert([
    [
        'id' => '019edef7-799e-706e-8a3b-d77143182225',
        'username' => 'admin',
        'name' => 'Admin Persediaan',
        'email' => 'admin@mail.com',
        'password' => bcrypt('password'),
        'role' => 'admin',
        'created_at' => '2026-06-19 08:20:21',
        'updated_at' => '2026-08-03 18:22:16',
    ],
    [
        'id' => '019edef7-8b13-71f3-a3cd-6652afa9716c',
        'username' => 'sekretaris',
        'name' => 'Sekretaris',
        'email' => 'hagenes.kris@example.net',
        'password' => bcrypt('password123'),
        'role' => 'customer',
        'created_at' => '2026-06-19 08:20:25',
        'updated_at' => '2026-08-03 18:26:54',
    ],
    [
        'id' => '019edef7-7f0d-7254-9c24-13c85fd860db',
        'username' => 'distribusi',
        'name' => 'Tim Distribusi',
        'email' => 'wilburn.feeney@example.org',
        'password' => bcrypt('password123'),
        'role' => 'customer',
        'created_at' => '2026-06-19 08:20:22',
        'updated_at' => '2026-08-03 18:24:35',
    ],
    [
        'id' => '019edef7-7db9-70eb-96d0-b17979abba11',
        'username' => 'ipj',
        'name' => 'Tim IPJ',
        'email' => 'itillman@example.com',
        'password' => bcrypt('password123'),
        'role' => 'customer',
        'created_at' => '2026-06-19 08:20:22',
        'updated_at' => '2026-08-03 18:24:50',
    ],
    [
        'id' => '019edef7-81b7-70cd-b242-f214672d8192',
        'username' => 'keuangan',
        'name' => 'Tim Keuangan',
        'email' => 'rocio68@example.net',
        'password' => bcrypt('password123'),
        'role' => 'customer',
        'created_at' => '2026-06-19 08:20:23',
        'updated_at' => '2026-08-03 18:25:54',
    ],
    [
        'id' => '019edef7-8062-72ab-a906-3954f9391e07',
        'username' => 'nerwilis',
        'name' => 'Tim Nerwilis',
        'email' => 'heaney.janae@example.org',
        'password' => bcrypt('password123'),
        'role' => 'customer',
        'created_at' => '2026-06-19 08:20:22',
        'updated_at' => '2026-08-03 18:24:20',
    ],
    [
        'id' => '019edef7-870e-702c-881a-199327d85946',
        'username' => 'pbj',
        'name' => 'Tim PBJ',
        'email' => 'marty95@example.net',
        'password' => bcrypt('password123'),
        'role' => 'customer',
        'created_at' => '2026-06-19 08:20:24',
        'updated_at' => '2026-08-03 18:26:18',
    ],
    [
        'id' => '019edef7-8866-7165-92cf-2e871ec63c04',
        'username' => 'perencanaan',
        'name' => 'Tim Perencanaan',
        'email' => 'rhea07@example.com',
        'password' => bcrypt('password123'),
        'role' => 'customer',
        'created_at' => '2026-06-19 08:20:24',
        'updated_at' => '2026-08-03 18:26:09',
    ],
    [
        'id' => '019edef7-7c67-71fe-990e-bd7609fac0bf',
        'username' => 'produksi',
        'name' => 'Tim Produksi',
        'email' => 'boyle.maymie@example.net',
        'password' => bcrypt('password123'),
        'role' => 'customer',
        'created_at' => '2026-06-19 08:20:21',
        'updated_at' => '2026-08-03 18:24:00',
    ],
    [
        'id' => '019edef7-8316-727b-88ea-06fd750becf4',
        'username' => 'pss',
        'name' => 'Tim PSS EPSS',
        'email' => 'emmalee.gleason@example.com',
        'password' => bcrypt('password123'),
        'role' => 'customer',
        'created_at' => '2026-06-19 08:20:23',
        'updated_at' => '2026-08-03 18:25:17',
    ],
    [
        'id' => '019edef7-85b7-733d-aff3-8a4af74f5590',
        'username' => 'sdm',
        'name' => 'Tim SDM',
        'email' => 'haylie.fadel@example.org',
        'password' => bcrypt('password123'),
        'role' => 'customer',
        'created_at' => '2026-06-19 08:20:24',
        'updated_at' => '2026-08-03 18:26:27',
    ],
    [
        'id' => '019edef7-7b11-73c9-8ad2-28d965624729',
        'username' => 'sosial',
        'name' => 'Tim Sosial',
        'email' => 'roel32@example.net',
        'password' => bcrypt('password123'),
        'role' => 'customer',
        'created_at' => '2026-06-19 08:20:21',
        'updated_at' => '2026-08-03 18:22:50',
    ],
    [
        'id' => '019edef7-8c70-72a9-8b00-7036b7e71536',
        'username' => 'umum',
        'name' => 'Tim Umum',
        'email' => 'rickie25@example.org',
        'password' => bcrypt('password123'),
        'role' => 'customer',
        'created_at' => '2026-06-19 08:20:25',
        'updated_at' => '2026-08-03 18:26:37',
    ],
    [
        'id' => '019edef7-8466-7350-b62f-278a1e009fcd',
        'username' => 'yanmas',
        'name' => 'Tim Yanmas',
        'email' => 'colby.effertz@example.net',
        'password' => bcrypt('password123'),
        'role' => 'customer',
        'created_at' => '2026-06-19 08:20:23',
        'updated_at' => '2026-08-03 18:25:02',
    ],
        ]);
    }
}





