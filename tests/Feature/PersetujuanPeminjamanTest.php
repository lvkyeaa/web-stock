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

class PersetujuanPeminjamanTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        $username = Str::lower(Str::random(8));
        $user = User::forceCreate(['name' => $username, 'username' => $username, 'email' => "$username@example.com", 'password' => 'x']);
        $user->assignRole($role);

        return $user;
    }

    public function test_menyetujui_dengan_fasilitas_yang_sudah_dipakai_mengembalikan_pesan_dan_peminjaman_yang_bentrok(): void
    {
        Notification::fake();
        $jenis = FacilityType::create(['code' => 'room', 'name' => 'Ruang Rapat', 'icon' => '🏢', 'color' => '#0284c7']);
        $ruangA = Facility::create(['facility_type_id' => $jenis->id, 'name' => 'Ruang A']);
        $ruangB = Facility::create(['facility_type_id' => $jenis->id, 'name' => 'Ruang B']);

        $pemilikB = $this->user('customer');
        $sudahDisetujui = Peminjaman::create([
            'user_id' => $pemilikB->id, 'facility_request_id' => $ruangB->id, 'facility_id' => $ruangB->id,
            'waktu_mulai' => '2026-11-02 09:00', 'waktu_selesai' => '2026-11-02 11:00', 'keperluan' => 'Rapat koordinasi', 'status' => 'disetujui',
        ]);
        $pengajuan = Peminjaman::create([
            'user_id' => $this->user('customer')->id, 'facility_request_id' => $ruangA->id,
            'waktu_mulai' => '2026-11-02 10:00', 'waktu_selesai' => '2026-11-02 12:00', 'keperluan' => 'Pelatihan', 'status' => 'pending',
        ]);

        // Admin mengganti fasilitas ke Ruang B yang sudah dipakai pada jam tersebut
        $this->actingAs($this->user('admin'))
            ->patchJson(route('peminjaman.update-status', $pengajuan->id), ['status' => 'disetujui', 'facility_id' => $ruangB->id])
            ->assertUnprocessable()
            ->assertExactJson([
                'message'   => 'Fasilitas ini sudah dipakai peminjaman lain yang disetujui pada waktu tersebut. Pilih waktu atau fasilitas lain.',
                'errors'    => ['facility_id' => ['Fasilitas ini sudah dipakai peminjaman lain yang disetujui pada waktu tersebut. Pilih waktu atau fasilitas lain.']],
                'conflicts' => [[
                    'id'          => $sudahDisetujui->id,
                    'facility'    => 'Ruang B',
                    'user'        => $pemilikB->username,
                    'start_label' => '2 November 2026, 09:00',
                    'end_label'   => '2 November 2026, 11:00',
                    'keperluan'   => 'Rapat koordinasi',
                ]],
            ]);

        $this->assertSame(['pending', null], [$pengajuan->fresh()->status, $pengajuan->fresh()->facility_id]);

        // Fasilitas lain yang kosong tetap bisa disetujui
        $this->actingAs($this->user('admin'))
            ->patchJson(route('peminjaman.update-status', $pengajuan->id), ['status' => 'disetujui', 'facility_id' => $ruangA->id])
            ->assertOk();
        $this->assertSame(['disetujui', $ruangA->id], [$pengajuan->fresh()->status, $pengajuan->fresh()->facility_id]);
    }

    public function test_dialog_persetujuan_menampilkan_galat_dan_bentrok(): void
    {
        $this->actingAs($this->user('admin'))->get(route('peminjaman.index'))
            ->assertOk()
            ->assertSee('x-text="statusError"', false)
            ->assertSee('x-for="c in statusConflicts"', false);
    }

    public function test_kalender_tidak_disembunyikan_dengan_display_none_saat_tab_tabel(): void
    {
        // FullCalendar yang digambar saat display:none mengukur lebar 0 dan rusak saat dibuka lagi
        $this->actingAs($this->user('customer'))->get(route('peminjaman.index', ['view' => 'list']))
            ->assertOk()
            ->assertDontSee("x-show=\"tab === 'calendar'\"", false)
            ->assertSee(":class=\"tab === 'calendar' ? '' : 'h-0 overflow-hidden invisible'\"", false);
    }
}
