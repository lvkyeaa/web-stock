<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class RiwayatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $requests = \App\Models\Order::all();
        $admin = \App\Models\User::role('admin')->first();

        foreach ($requests as $req) {
            // Log saat pengajuan dibuat oleh user
            \App\Models\Riwayat::create([
                'order_id' => $req->id,
                'actor_id' => $req->user_id,
                'status_sebelumnya' => null,
                'status_sesudah' => 'pending',
                'catatan' => 'Mengajukan permintaan barang.',
                'created_at' => $req->created_at,
            ]);

            // Jika statusnya sudah berubah (disetujui/ditolak), buat log adminnya
            if ($req->status !== 'pending') {
                \App\Models\Riwayat::create([
                    'order_id' => $req->id,
                    'actor_id' => $admin->id,
                    'status_sebelumnya' => 'pending',
                    'status_sesudah' => $req->status,
                    'catatan' => $req->status === 'disetujui' ? 'Permintaan disetujui admin.' : 'Permintaan ditolak: ' . $req->alasan,
                    'created_at' => $req->updated_at,
                ]);
            }
        }
    }
}
