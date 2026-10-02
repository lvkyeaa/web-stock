<?php

namespace Database\Seeders;

use App\Models\Facility;
use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Database\Seeder;

// Data contoh dalam jumlah besar untuk melihat tampilan kalender/daftar saat permintaan ramai.
// Jalankan terpisah: php artisan db:seed --class=CrowdedBookingSeeder
class CrowdedBookingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        mt_srand(2026); // hasil acak tetap sama setiap dijalankan

        $facilities = Facility::with('facilityType')->get();
        $customers = User::role('customer')->pluck('id')->all();

        $keperluan = [
            'car'  => ['Kunjungan lapangan survei harga', 'Pengawasan pencacahan Susenas', 'Koordinasi ke BPS kabupaten', 'Pengantaran dokumen ke Kanwil', 'Monitoring survei ubinan', 'Perjalanan dinas rapat provinsi'],
            'room' => ['Rapat koordinasi tim', 'Pelatihan petugas lapangan', 'Rapat evaluasi kegiatan', 'Sosialisasi aplikasi', 'Rapat persiapan rilis BRS', 'Diskusi teknis metodologi'],
            'zoom' => ['Webinar rilis statistik', 'Briefing petugas se-provinsi', 'Rapat daring dengan BPS RI', 'Pembinaan statistik sektoral', 'Koordinasi daring tim kabupaten'],
        ];
        $statuses = ['disetujui', 'disetujui', 'disetujui', 'disetujui', 'disetujui', 'pending', 'pending', 'pending', 'ditolak', 'ditolak'];

        // Minggu lalu sampai 2 minggu ke depan, Senin–Jumat
        $mulaiPeriode = now()->startOfWeek()->subWeek();
        $rows = [];

        for ($hari = 0; $hari < 28; $hari++) {
            $tanggal = $mulaiPeriode->copy()->addDays($hari);
            if ($tanggal->isWeekend()) {
                continue;
            }

            foreach ($facilities as $facility) {
                $code = $facility->facilityType->code;
                $daftarKeperluan = $keperluan[$code] ?? $keperluan['room'];
                $isCar = $code === 'car';

                // Slot berurutan (kursor waktu) tanpa tumpang tindih, jadi peminjaman disetujui tidak pernah bentrok
                $kursor = $tanggal->copy()->setTime(7, 0);
                $batas = $tanggal->copy()->setTime(18, 0);
                while (true) {
                    $mulai = $kursor->copy()->addMinutes(mt_rand(0, 4) * 30);
                    $selesai = $mulai->copy()->addHours($isCar ? mt_rand(2, 8) : mt_rand(1, 3));
                    if ($selesai > $batas) {
                        break;
                    }

                    $status = $statuses[array_rand($statuses)];

                    // Jangan bentrok dengan peminjaman disetujui yang sudah ada (mis. dari BookingSeeder)
                    if ($status === 'disetujui' && $this->bentrok($facility->id, $mulai, $selesai)) {
                        $status = 'pending';
                    }

                    $rows[] = $this->row($customers, $facility->id, $mulai, $selesai, $daftarKeperluan, $status);

                    // Permintaan ramai: pengajuan lain untuk slot yang sama ikut menunggu persetujuan
                    if ($status === 'pending' && mt_rand(1, 100) <= 40) {
                        $rows[] = $this->row($customers, $facility->id, $mulai->copy()->addMinutes(30), $selesai->copy()->addMinutes(30), $daftarKeperluan, 'pending');
                    }

                    $kursor = $selesai;
                }
            }
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            Peminjaman::insert($chunk);
        }

        $this->command?->info(count($rows) . ' peminjaman contoh (ramai) dibuat.');
    }

    private function bentrok(string $facilityId, $mulai, $selesai): bool
    {
        return Peminjaman::where('facility_id', $facilityId)
            ->where('status', 'disetujui')
            ->where('waktu_mulai', '<', $selesai)
            ->where('waktu_selesai', '>', $mulai)
            ->exists();
    }

    private function row(array $customers, string $facilityId, $mulai, $selesai, array $daftarKeperluan, string $status): array
    {
        return [
            'user_id' => $customers[array_rand($customers)],
            'facility_request_id' => $facilityId,
            'facility_id' => $status === 'disetujui' ? $facilityId : null,
            'waktu_mulai' => $mulai,
            'waktu_selesai' => $selesai,
            'keperluan' => $daftarKeperluan[array_rand($daftarKeperluan)],
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
