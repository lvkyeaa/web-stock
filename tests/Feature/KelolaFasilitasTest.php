<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\FacilityType;
use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

// Admin mengelola fasilitas; kapasitas hanya untuk ruang rapat
class KelolaFasilitasTest extends TestCase
{
    use RefreshDatabase;

    private FacilityType $mobil;
    private FacilityType $ruangRapat;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mobil = FacilityType::create(['code' => 'car', 'name' => 'Mobil Dinas', 'icon' => '🚗', 'color' => '#f97316']);
        $this->ruangRapat = FacilityType::create(['code' => 'room', 'name' => 'Ruang Rapat', 'icon' => '🏢', 'color' => '#0284c7']);
    }

    private function user(string $role): User
    {
        $username = Str::lower(Str::random(8));
        $user = User::forceCreate(['name' => $username, 'username' => $username, 'email' => "$username@example.com", 'password' => 'x']);
        $user->assignRole($role);

        return $user;
    }

    private function pinjam(Facility $facility, bool $hapus = false): void
    {
        $besok = now()->addDay()->toDateString();
        $peminjaman = Peminjaman::create([
            'user_id' => $this->user('customer')->id, 'facility_request_id' => $facility->id,
            'waktu_mulai' => "$besok 09:00", 'waktu_selesai' => "$besok 10:00", 'keperluan' => 'Rapat', 'status' => 'pending',
        ]);
        if ($hapus) {
            $peminjaman->delete();
        }
    }

    public function test_hanya_admin_yang_bisa_membuka_halaman(): void
    {
        $this->get(route('admin.fasilitas.index'))->assertRedirect(route('login'));
        $this->actingAs($this->user('customer'))->get(route('admin.fasilitas.index'))->assertForbidden();
        $response = $this->actingAs($this->user('admin'))->get(route('admin.fasilitas.index'))->assertOk()->assertSee('Tambah Fasilitas')
            ->assertSee('<title>Fasilitas — BPS Provinsi Jawa Timur</title>', false);

        // Menu sidebar Fasilitas ditandai aktif
        $this->assertMatchesRegularExpression('#href="' . preg_quote(route('admin.fasilitas.index'), '#') . '"\s+aria-current="page"#', $response->getContent());
    }

    public function test_ruang_rapat_wajib_punya_kapasitas(): void
    {
        $admin = $this->user('admin');

        $this->actingAs($admin)
            ->postJson(route('admin.fasilitas.store'), ['facility_type_id' => $this->ruangRapat->id, 'name' => 'Ruang Vicon'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.capacity.0', 'Kapasitas ruang rapat wajib diisi.');

        $this->actingAs($admin)
            ->postJson(route('admin.fasilitas.store'), ['facility_type_id' => $this->ruangRapat->id, 'name' => 'Ruang Vicon', 'capacity' => 20])
            ->assertCreated()
            ->assertJsonPath('message', 'Fasilitas Ruang Vicon berhasil ditambahkan.')
            ->assertJsonPath('data.name', 'Ruang Vicon')
            ->assertJsonPath('data.capacity', 20)
            ->assertJsonPath('data.type.code', 'room')
            ->assertJsonPath('data.dipakai', false);

        $this->assertSame(20, Facility::where('name', 'Ruang Vicon')->value('capacity'));
    }

    public function test_jenis_selain_ruang_rapat_tidak_menyimpan_kapasitas(): void
    {
        $admin = $this->user('admin');

        $this->actingAs($admin)
            ->postJson(route('admin.fasilitas.store'), ['facility_type_id' => $this->mobil->id, 'name' => 'L 1760 HP', 'capacity' => 7])
            ->assertCreated()
            ->assertJsonPath('data.capacity', null);
        $this->assertNull(Facility::where('name', 'L 1760 HP')->value('capacity'));

        // Ruang rapat diubah menjadi jenis lain (belum pernah dipinjam): kapasitas dikosongkan
        $ruang = Facility::create(['facility_type_id' => $this->ruangRapat->id, 'name' => 'Ruang A', 'capacity' => 10]);
        $this->actingAs($admin)
            ->putJson(route('admin.fasilitas.update', $ruang), ['facility_type_id' => $this->mobil->id, 'name' => 'Ruang A', 'capacity' => 10])
            ->assertOk()
            ->assertJsonPath('data.type.code', 'car') // jenis baru, bukan relasi lama
            ->assertJsonPath('data.capacity', null);
        $this->assertNull($ruang->fresh()->capacity);
    }

    public function test_nama_tidak_boleh_ganda_dalam_jenis_yang_sama(): void
    {
        Facility::create(['facility_type_id' => $this->mobil->id, 'name' => 'Avanza']);

        $this->actingAs($this->user('admin'))
            ->postJson(route('admin.fasilitas.store'), ['facility_type_id' => $this->mobil->id, 'name' => 'Avanza'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.name.0', 'Fasilitas dengan nama ini sudah ada.');
    }

    public function test_ubah_nama_dan_kapasitas(): void
    {
        $ruang = Facility::create(['facility_type_id' => $this->ruangRapat->id, 'name' => 'Ruang A', 'capacity' => 10]);
        $this->pinjam($ruang); // sudah dipinjam: nama & kapasitas tetap boleh diubah

        $this->actingAs($this->user('admin'))
            ->putJson(route('admin.fasilitas.update', $ruang), ['facility_type_id' => $this->ruangRapat->id, 'name' => 'Ruang Aula', 'capacity' => 50])
            ->assertOk()
            ->assertJsonPath('data.name', 'Ruang Aula')
            ->assertJsonPath('data.capacity', 50)
            ->assertJsonPath('data.dipakai', true);

        $ruang->refresh();
        $this->assertSame('Ruang Aula', $ruang->name);
        $this->assertSame(50, $ruang->capacity);
    }

    public function test_jenis_tidak_bisa_diubah_setelah_dipinjam(): void
    {
        $ruang = Facility::create(['facility_type_id' => $this->ruangRapat->id, 'name' => 'Ruang A', 'capacity' => 10]);
        $this->pinjam($ruang);

        $this->actingAs($this->user('admin'))
            ->putJson(route('admin.fasilitas.update', $ruang), ['facility_type_id' => $this->mobil->id, 'name' => 'Ruang A'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.facility_type_id.0', 'Jenis fasilitas tidak dapat diubah karena fasilitas ini sudah dipakai peminjaman.');

        $this->assertSame($this->ruangRapat->id, $ruang->fresh()->facility_type_id);
    }

    public function test_daftar_per_halaman_dengan_filter_jenis(): void
    {
        foreach (range(1, 12) as $i) {
            Facility::create(['facility_type_id' => $this->mobil->id, 'name' => sprintf('Mobil %02d', $i)]);
        }
        Facility::create(['facility_type_id' => $this->ruangRapat->id, 'name' => 'Ruang A', 'capacity' => 10]);
        $admin = $this->user('admin');

        // Urut per jenis (Mobil Dinas, lalu Ruang Rapat), lalu nama; 10 per halaman
        $response = $this->actingAs($admin)->getJson(route('admin.fasilitas.data', ['page' => 2]))
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.name', 'Mobil 11')
            ->assertJsonPath('data.2.name', 'Ruang A')
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('meta.from', 11)
            ->assertJsonPath('meta.total', 13);
        $this->assertEquals(['' => 13, $this->mobil->id => 12, $this->ruangRapat->id => 1], $response->json('meta.counts'));

        $response = $this->actingAs($admin)->getJson(route('admin.fasilitas.data', ['type_id' => $this->ruangRapat->id]))
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Ruang A')
            ->assertJsonPath('meta.total', 1);
        $this->assertSame(13, $response->json('meta.counts')['']); // jumlah per jenis tidak ikut tersaring
    }

    public function test_fasilitas_yang_pernah_dipinjam_tidak_bisa_dihapus(): void
    {
        $admin = $this->user('admin');
        $baru = Facility::create(['facility_type_id' => $this->mobil->id, 'name' => 'Avanza']);
        $dipakai = Facility::create(['facility_type_id' => $this->mobil->id, 'name' => 'Innova']);
        $this->pinjam($dipakai, hapus: true); // peminjaman yang sudah dihapus (soft delete) tetap dihitung

        $this->actingAs($admin)->getJson(route('admin.fasilitas.data'))
            ->assertJsonPath('data.0.name', 'Avanza')
            ->assertJsonPath('data.0.dipakai', false)
            ->assertJsonPath('data.1.dipakai', true);

        $this->actingAs($admin)->deleteJson(route('admin.fasilitas.destroy', $dipakai))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Innova tidak dapat dihapus karena sudah dipakai peminjaman.');
        $this->assertSame(1, Peminjaman::withTrashed()->count());

        $this->actingAs($admin)->deleteJson(route('admin.fasilitas.destroy', $baru))
            ->assertOk()
            ->assertJsonPath('data.id', $baru->id);
        $this->assertModelMissing($baru);
    }
}
