<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\FacilityType;
use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class HalamanBuatPeminjamanTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        $username = Str::lower(Str::random(8));
        $user = User::forceCreate(['name' => $username, 'username' => $username, 'email' => "$username@example.com", 'password' => 'x']);
        $user->assignRole($role);

        return $user;
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get(route('peminjaman.create'))->assertRedirect(route('login'));
    }

    public function test_halaman_menampilkan_pilihan_jenis_fasilitas(): void
    {
        FacilityType::create(['code' => 'car', 'name' => 'Mobil Dinas', 'icon' => '🚗', 'color' => '#f97316']);
        FacilityType::create(['code' => 'room', 'name' => 'Ruang Rapat', 'icon' => '🏢', 'color' => '#0284c7']);

        foreach (['admin', 'customer'] as $role) {
            $this->actingAs($this->user($role))->get(route('peminjaman.create'))
                ->assertOk()
                ->assertSee('Mobil Dinas')
                ->assertSee('Ruang Rapat')
                ->assertSee(route('peminjaman.store'), false);
        }
    }

    public function test_form_mengikuti_validasi_dan_logika_peminjaman(): void
    {
        Notification::fake();
        $jenis = FacilityType::create(['code' => 'room', 'name' => 'Ruang Rapat', 'icon' => '🏢', 'color' => '#0284c7']);
        $ruang = Facility::create(['facility_type_id' => $jenis->id, 'name' => 'Ruang A']);
        $customer = $this->user('customer');
        $besok = now()->addDay()->toDateString();

        // Selesai di hari berbeda: ditolak validasi, kembali ke halaman buat dengan input lama
        $this->actingAs($customer)->from(route('peminjaman.create'))
            ->post(route('peminjaman.store'), [
                'facility_request_id' => $ruang->id, 'waktu_mulai' => "$besok 09:00",
                'waktu_selesai' => now()->addDays(2)->toDateString() . ' 10:00', 'keperluan' => 'Rapat',
            ])
            ->assertRedirect(route('peminjaman.create'))
            ->assertSessionHasErrors('waktu_selesai');

        // Jenis fasilitas terpilih kembali dari fasilitas pada input lama
        $this->get(route('peminjaman.create'))->assertOk()->assertSee('\u0022jenis\u0022:\u0022room\u0022', false);

        // Valid: customer menunggu persetujuan
        $this->from(route('peminjaman.create'))
            ->post(route('peminjaman.store'), [
                'facility_request_id' => $ruang->id, 'waktu_mulai' => "$besok 09:00",
                'waktu_selesai' => "$besok 10:00", 'keperluan' => 'Rapat', 'jumlah_peserta' => 10,
            ])
            ->assertRedirect(route('peminjaman.create'))
            ->assertSessionHas('success');

        $this->assertSame('pending', Peminjaman::sole()->status);
    }
}
