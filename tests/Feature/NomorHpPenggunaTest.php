<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

// Admin mengisi nomor HP pengguna (opsional) saat tambah / ubah pengguna
class NomorHpPenggunaTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        $username = Str::lower(Str::random(8));
        $user = User::forceCreate(['name' => $username, 'username' => $username, 'email' => "$username@example.com", 'password' => 'x']);
        $user->assignRole($role);

        return $user;
    }

    public function test_tambah_pengguna_menyimpan_nomor_hp(): void
    {
        $this->actingAs($this->user('admin'))
            ->postJson(route('admin.manajemen-user.store'), [
                'name' => 'Budi', 'email' => 'budi@example.com', 'phone' => '6281234567890', 'password' => 'rahasia', 'role' => 'customer',
            ])
            ->assertOk();

        $this->assertSame('6281234567890', User::where('email', 'budi@example.com')->value('phone'));
    }

    public function test_nomor_hp_opsional(): void
    {
        $this->actingAs($this->user('admin'))
            ->postJson(route('admin.manajemen-user.store'), [
                'name' => 'Budi', 'email' => 'budi@example.com', 'phone' => '', 'password' => 'rahasia', 'role' => 'customer',
            ])
            ->assertOk();

        $this->assertNull(User::where('email', 'budi@example.com')->value('phone'));
    }

    public function test_nomor_hp_harus_diawali_62_dan_hanya_angka(): void
    {
        $this->actingAs($this->user('admin'));

        foreach (['081234567890', '+6281234567890', '62 812-3456-7890', '6281', 'bukan nomor'] as $phone) {
            $this->postJson(route('admin.manajemen-user.store'), [
                'name' => 'Budi', 'email' => 'budi@example.com', 'phone' => $phone, 'password' => 'rahasia', 'role' => 'customer',
            ])->assertJsonValidationErrors('phone');
        }
    }

    public function test_ubah_pengguna_mengganti_nomor_hp_dan_tampil_di_daftar(): void
    {
        $admin = $this->user('admin');
        $user = $this->user('customer');

        $this->actingAs($admin)
            ->putJson(route('admin.manajemen-user.update', $user->id), [
                'name' => $user->name, 'email' => $user->email, 'phone' => '6281234567890', 'role' => 'customer',
            ])
            ->assertOk();

        $this->assertSame('6281234567890', $user->fresh()->phone);
        $this->getJson(route('admin.manajemen-user.data'))
            ->assertJsonFragment(['id' => $user->id, 'phone' => '6281234567890']);
    }
}
