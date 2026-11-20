<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

// Halaman profil: pengguna melihat data akunnya dan mengubah nomor WhatsApp sendiri
class ProfilTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, ?string $phone = null): User
    {
        $username = Str::lower(Str::random(8));
        $user = User::forceCreate(['name' => "Nama $username", 'username' => $username, 'email' => "$username@example.com", 'password' => 'x', 'phone' => $phone]);
        $user->assignRole($role);

        return $user;
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get(route('profil.index'))->assertRedirect(route('login'));
    }

    public function test_admin_dan_customer_melihat_profilnya_dan_menu_profil(): void
    {
        foreach (['admin', 'customer'] as $role) {
            $user = $this->user($role, '6281234567890');

            $this->actingAs($user)->get(route('profil.index'))->assertOk()
                ->assertSee($user->name)
                ->assertSee($user->email)
                ->assertSee('value="6281234567890"', false)
                ->assertSee('href="' . route('profil.index') . '"', false); // tautan di menu akun
        }
    }

    public function test_mengubah_nomor_whatsapp_sendiri(): void
    {
        $user = $this->user('customer');

        $this->actingAs($user)->put(route('profil.update'), ['phone' => '6289876543210'])
            ->assertRedirect(route('profil.index'))
            ->assertSessionHas('success');

        $this->assertSame('6289876543210', $user->fresh()->phone);
    }

    public function test_nomor_tidak_valid_atau_kosong_ditolak(): void
    {
        $user = $this->user('customer', '6281234567890');

        foreach (['', '081234567890', '+6281234567890', '62 812 3456', '6281'] as $phone) {
            $this->actingAs($user)->from(route('profil.index'))->put(route('profil.update'), ['phone' => $phone])
                ->assertRedirect(route('profil.index'))
                ->assertSessionHasErrors('phone');
        }

        $this->assertSame('6281234567890', $user->fresh()->phone);
    }

    public function test_hanya_mengubah_nomor_sendiri_bukan_data_lain(): void
    {
        $user = $this->user('customer');
        $lain = $this->user('customer', '6281111111111');

        $this->actingAs($user)->put(route('profil.update'), ['phone' => '6289876543210', 'name' => 'Diganti', 'email' => 'x@example.com']);

        $user->refresh();
        $this->assertNotSame('Diganti', $user->name);
        $this->assertNotSame('x@example.com', $user->email);
        $this->assertSame('6281111111111', $lain->fresh()->phone);
    }
}
