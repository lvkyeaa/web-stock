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

// Detail tambahan peminjaman ruang rapat: jumlah peserta (dibatasi kapasitas), sound system, konsumsi
class PeminjamanRuangRapatTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        $username = Str::lower(Str::random(8));
        $user = User::forceCreate(['name' => $username, 'username' => $username, 'email' => "$username@example.com", 'password' => 'x']);
        $user->assignRole($role);

        return $user;
    }

    private function ruang(string $name, ?int $capacity): Facility
    {
        $jenis = FacilityType::firstOrCreate(['code' => 'room'], ['name' => 'Ruang Rapat', 'icon' => '🏢', 'color' => '#0284c7']);

        return Facility::create(['facility_type_id' => $jenis->id, 'name' => $name, 'capacity' => $capacity]);
    }

    private function waktu(): array
    {
        $besok = now()->addDay()->toDateString();

        return ['waktu_mulai' => "$besok 09:00", 'waktu_selesai' => "$besok 11:00", 'keperluan' => 'Rapat koordinasi'];
    }

    public function test_jumlah_peserta_wajib_dan_tidak_boleh_melebihi_kapasitas(): void
    {
        $ruang = $this->ruang('Ruang A', 10);
        $customer = $this->user('customer');

        $this->actingAs($customer)
            ->postJson(route('peminjaman.store'), ['facility_request_id' => $ruang->id] + $this->waktu())
            ->assertUnprocessable()
            ->assertJsonPath('errors.jumlah_peserta.0', 'Jumlah peserta wajib diisi.');

        $this->actingAs($customer)
            ->postJson(route('peminjaman.store'), ['facility_request_id' => $ruang->id, 'jumlah_peserta' => 11] + $this->waktu())
            ->assertUnprocessable()
            ->assertJsonPath('errors.jumlah_peserta.0', 'Kapasitas Ruang A hanya 10 orang.');

        $this->assertSame(0, Peminjaman::count());
    }

    public function test_form_peminjaman_menerima_kapasitas_ruangan(): void
    {
        $this->ruang('Ruang A', 30);
        $customer = $this->user('customer');

        // Daftar fasilitas di halaman buat & kalender (lewat @js) membawa kapasitas untuk info di form
        foreach (['peminjaman.create', 'peminjaman.index'] as $route) {
            $this->actingAs($customer)->get(route($route))->assertOk()
                ->assertSee(str_replace('"', '\\' . 'u0022', '"name":"Ruang A","capacity":30'), false);
        }
    }

    public function test_ruang_tanpa_kapasitas_tidak_dibatasi(): void
    {
        Notification::fake();
        $ruang = $this->ruang('Aula', null);

        $this->actingAs($this->user('customer'))
            ->postJson(route('peminjaman.store'), ['facility_request_id' => $ruang->id, 'jumlah_peserta' => 500] + $this->waktu())
            ->assertOk();

        $this->assertSame(500, Peminjaman::first()->jumlah_peserta);
    }

    public function test_jumlah_peserta_dibatasi_batas_kolom_database(): void
    {
        Notification::fake();
        $ruang = $this->ruang('Aula', null);
        $customer = $this->user('customer');

        // Melebihi unsigned smallint: ditolak validasi (bukan galat database), baik saat buat maupun ubah
        $this->actingAs($customer)
            ->postJson(route('peminjaman.store'), ['facility_request_id' => $ruang->id, 'jumlah_peserta' => 70000] + $this->waktu())
            ->assertUnprocessable()
            ->assertJsonPath('errors.jumlah_peserta.0', 'Jumlah peserta maksimal 65535 orang.');

        $peminjaman = Peminjaman::create([
            'user_id' => $customer->id, 'facility_request_id' => $ruang->id, 'jumlah_peserta' => 10, 'status' => 'pending',
        ] + $this->waktu());
        $this->actingAs($customer)
            ->putJson(route('peminjaman.update', $peminjaman->id), ['facility_request_id' => $ruang->id, 'jumlah_peserta' => 70000] + $this->waktu())
            ->assertUnprocessable()
            ->assertJsonPath('errors.jumlah_peserta.0', 'Jumlah peserta maksimal 65535 orang.');

        $this->actingAs($customer)
            ->postJson(route('peminjaman.store'), ['facility_request_id' => $ruang->id, 'jumlah_peserta' => 65535] + $this->waktu())
            ->assertOk();
    }

    public function test_detail_ruang_tersimpan_dan_tampil_di_data_kalender(): void
    {
        Notification::fake();
        $ruang = $this->ruang('Ruang A', 30);
        $customer = $this->user('customer');

        // Kotak centang yang tidak dicentang tidak terkirim: tersimpan false
        $this->actingAs($customer)
            ->postJson(route('peminjaman.store'), [
                'facility_request_id' => $ruang->id, 'jumlah_peserta' => 20, 'butuh_sound_system' => '1',
            ] + $this->waktu())
            ->assertOk();

        $peminjaman = Peminjaman::first();
        $this->assertSame(20, $peminjaman->jumlah_peserta);
        $this->assertTrue($peminjaman->butuh_sound_system);
        $this->assertFalse($peminjaman->butuh_konsumsi);

        $this->actingAs($customer)
            ->getJson(route('peminjaman.data', ['start' => now()->toDateString(), 'end' => now()->addDays(3)->toDateString()]))
            ->assertJsonPath('data.0.jumlah_peserta', 20)
            ->assertJsonPath('data.0.butuh_sound_system', true)
            ->assertJsonPath('data.0.butuh_konsumsi', false)
            ->assertJsonPath('data.0.edit.jumlah_peserta', 20);

        // Edit oleh pemilik memperbarui detail ruang
        $this->actingAs($customer)
            ->putJson(route('peminjaman.update', $peminjaman->id), [
                'facility_request_id' => $ruang->id, 'jumlah_peserta' => 25, 'butuh_konsumsi' => '1',
            ] + $this->waktu())
            ->assertOk();

        $peminjaman->refresh();
        $this->assertSame(25, $peminjaman->jumlah_peserta);
        $this->assertFalse($peminjaman->butuh_sound_system);
        $this->assertTrue($peminjaman->butuh_konsumsi);
    }

    public function test_jenis_selain_ruang_mengabaikan_detail_ruang(): void
    {
        Notification::fake();
        $mobil = FacilityType::create(['code' => 'car', 'name' => 'Mobil Dinas', 'icon' => '🚗', 'color' => '#f97316']);
        $avanza = Facility::create(['facility_type_id' => $mobil->id, 'name' => 'Avanza']);

        $this->actingAs($this->user('customer'))
            ->postJson(route('peminjaman.store'), [
                'facility_request_id' => $avanza->id, 'jumlah_peserta' => 5, 'butuh_sound_system' => '1', 'butuh_konsumsi' => '1',
            ] + $this->waktu())
            ->assertOk();

        $peminjaman = Peminjaman::first();
        $this->assertNull($peminjaman->jumlah_peserta);
        $this->assertFalse($peminjaman->butuh_sound_system);
        $this->assertFalse($peminjaman->butuh_konsumsi);
    }

    public function test_admin_tidak_bisa_memberikan_ruang_yang_terlalu_kecil(): void
    {
        Notification::fake();
        $besar = $this->ruang('Aula', 100);
        $kecil = $this->ruang('Ruang Kecil', 8);
        $pengajuan = Peminjaman::create([
            'user_id' => $this->user('customer')->id, 'facility_request_id' => $besar->id,
            'jumlah_peserta' => 40, 'status' => 'pending',
        ] + $this->waktu());
        $admin = $this->user('admin');

        $this->actingAs($admin)
            ->patchJson(route('peminjaman.update-status', $pengajuan->id), ['status' => 'disetujui', 'facility_id' => $kecil->id])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Kapasitas Ruang Kecil hanya 8 orang, sedangkan peserta 40 orang.');
        $this->assertSame('pending', $pengajuan->fresh()->status);

        $this->actingAs($admin)
            ->patchJson(route('peminjaman.update-status', $pengajuan->id), ['status' => 'disetujui', 'facility_id' => $besar->id])
            ->assertOk();
        $this->assertSame('disetujui', $pengajuan->fresh()->status);
    }
}
