<?php

namespace Database\Seeders;

use App\Models\Facility;
use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Database\Seeder;

class BookingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $facilities = Facility::pluck('id', 'name');
        $users = User::pluck('id', 'username');

        // Tanggal relatif terhadap minggu berjalan agar langsung terlihat di tampilan kalender mingguan
        $minggu = now()->startOfWeek();

        // [username, fasilitas, hari ke- (0 = Senin), jam mulai, jam selesai, status, keperluan]
        $bookings = [
            ['sekretaris', 'Ruang Aula Majapahit', 0, '09:00', '12:00', 'disetujui', 'Rapat koordinasi pimpinan'],
            ['distribusi', 'L 1760 HP', 0, '08:00', '15:00', 'disetujui', 'Kunjungan lapangan survei harga di Sidoarjo'],
            ['ipj', 'Ruang Vicon', 1, '10:00', '11:30', 'pending', 'Video conference dengan BPS RI'],
            ['produksi', 'L 38', 1, '07:30', '16:00', 'disetujui', 'Pengawasan pencacahan survei ubinan di Mojokerto'],
            ['keuangan', 'Ruang Vicon', 2, '13:00', '15:00', 'disetujui', 'Sosialisasi aplikasi SAKTI'],
            ['nerwilis', 'B 1877 PQS', 2, '08:00', '12:00', 'pending', 'Koordinasi dengan BPS Kota Surabaya'],
            ['sosial', 'L 1758 HP', 3, '08:00', '17:00', 'ditolak', 'Monitoring Susenas di Gresik'],
            ['sdm', 'Ruang Aula Majapahit', 3, '13:00', '16:00', 'pending', 'Pelatihan petugas Sakernas'],
            ['pbj', 'S 3351 NP', 4, '09:00', '13:00', 'disetujui', 'Pemeriksaan barang pengadaan di vendor'],
            ['perencanaan', 'Ruang Vicon', 4, '09:00', '10:00', 'ditolak', 'Rapat evaluasi anggaran'],
            ['yanmas', 'Ruang Aula Majapahit', 7, '08:00', '12:00', 'pending', 'Pelayanan statistik terpadu (minggu depan)'],
            ['umum', 'L 1759 HP', 8, '08:00', '14:00', 'disetujui', 'Pengantaran dokumen ke Kanwil DJPb (minggu depan)'],
            ['ipj', 'Zoom Kantor', 0, '14:00', '15:30', 'disetujui', 'Webinar rilis Berita Resmi Statistik'],
            ['produksi', 'Zoom Kantor', 1, '09:00', '10:00', 'pending', 'Briefing petugas survei ubinan se-Jawa Timur'],
            ['pss', 'Zoom Kantor', 2, '10:00', '12:00', 'disetujui', 'Pembinaan statistik sektoral OPD'],
            ['sosial', 'Zoom Kantor', 3, '15:00', '16:00', 'ditolak', 'Koordinasi tim Susenas kabupaten'],
            ['distribusi', 'Zoom Kantor', 4, '13:30', '15:00', 'pending', 'Rapat evaluasi Survei Harga Konsumen'],
            ['sdm', 'Zoom Kantor', 8, '10:00', '11:00', 'disetujui', 'Sosialisasi kebijakan kepegawaian (minggu depan)'],
        ];

        foreach ($bookings as [$username, $facility, $hari, $mulai, $selesai, $status, $keperluan]) {
            $tanggal = $minggu->copy()->addDays($hari)->toDateString();

            Peminjaman::create([
                'user_id' => $users[$username],
                'facility_request_id' => $facilities[$facility],
                'facility_id' => $status === 'disetujui' ? $facilities[$facility] : null,
                'waktu_mulai' => "$tanggal $mulai",
                'waktu_selesai' => "$tanggal $selesai",
                'keperluan' => $keperluan,
                'status' => $status,
            ]);
        }
    }
}
