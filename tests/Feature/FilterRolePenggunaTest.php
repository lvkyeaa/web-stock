<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

// Filter role di daftar pengguna (Manajemen User)
class FilterRolePenggunaTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, string $nama): User
    {
        $username = Str::lower(Str::random(8));
        $user = User::forceCreate(['name' => $nama, 'username' => $username, 'email' => "$username@example.com", 'password' => 'x']);
        $user->assignRole($role);

        return $user;
    }

    private function nama(array $params): array
    {
        return collect($this->getJson(route('admin.manajemen-user.data', $params))->assertOk()->json('data'))
            ->pluck('name')->sort()->values()->all();
    }

    public function test_daftar_pengguna_bisa_difilter_per_role(): void
    {
        $admin = $this->user('admin', 'Admin Satu');
        $this->user('admin', 'Admin Dua');
        $this->user('customer', 'Pengguna Satu');
        $this->actingAs($admin);

        $this->assertSame(['Admin Dua', 'Admin Satu', 'Pengguna Satu'], $this->nama([]));
        $this->assertSame(['Admin Dua', 'Admin Satu'], $this->nama(['role' => 'admin']));
        $this->assertSame(['Pengguna Satu'], $this->nama(['role' => 'customer']));
    }

    public function test_filter_ketua_tim_menampilkan_pengguna_yang_menjadi_ketua_tim(): void
    {
        $admin = $this->user('admin', 'Admin Satu');
        $ketua = $this->user('customer', 'Ketua Umum');
        $adminKetua = $this->user('admin', 'Admin Ketua');
        $this->user('customer', 'Pengguna Biasa');
        $umum = \App\Models\Team::create(['name' => 'Umum', 'short_name' => 'umum']);
        $ipds = \App\Models\Team::create(['name' => 'IPDS', 'short_name' => 'ipds']);
        $umum->chiefs()->attach([$ketua->id, $adminKetua->id]);
        $ipds->chiefs()->attach($ketua); // ketua beberapa tim tetap tampil sekali
        $this->actingAs($admin);

        $this->assertSame(['Admin Ketua', 'Ketua Umum'], $this->nama(['role' => 'ketua']));
        $this->assertSame(['Ketua Umum'], $this->nama(['role' => 'ketua', 'q' => 'Umum']));
        $this->assertContains('Ketua Umum', $this->nama(['role' => 'customer'])); // tetap muncul di role aslinya
    }

    public function test_filter_role_bisa_digabung_dengan_pencarian(): void
    {
        $admin = $this->user('admin', 'Admin Satu');
        $this->user('customer', 'Pengguna Satu');
        $this->user('customer', 'Pengguna Dua');
        $this->actingAs($admin);

        $this->assertSame(['Pengguna Satu'], $this->nama(['role' => 'customer', 'q' => 'Satu']));
    }

    public function test_role_tidak_dikenal_ditolak(): void
    {
        $this->actingAs($this->user('admin', 'Admin Satu'))
            ->getJson(route('admin.manajemen-user.data', ['role' => 'superadmin']))
            ->assertJsonValidationErrors('role');
    }

    public function test_halaman_menampilkan_filter_dan_mengingat_role_dari_url(): void
    {
        $this->actingAs($this->user('admin', 'Admin Satu'))
            ->get(route('admin.manajemen-user.index', ['role' => 'customer']))->assertOk()
            ->assertSee('Semua Role')
            ->assertViewHas('initial', fn ($initial) => ($initial['roles']['ketua'] ?? null) === 'Ketua Tim/PJ')
            ->assertViewHas('initial', fn ($initial) => $initial['role'] === 'customer');
    }
}
