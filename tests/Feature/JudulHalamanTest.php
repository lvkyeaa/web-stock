<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

// Judul halaman (tab browser & top bar) harus sama dengan nama menu di sidebar
class JudulHalamanTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        $username = Str::lower(Str::random(8));
        $user = User::forceCreate([
            'name' => $username, 'username' => $username, 'email' => "$username@example.com", 'password' => 'password',
        ]);
        $user->assignRole($role);

        return $user;
    }

    public function test_judul_halaman_admin_sama_dengan_menu_sidebar(): void
    {
        $admin = $this->user('admin');

        foreach ([
            'admin.dashboard'            => 'Dashboard',
            'barang.katalog'             => 'Katalog Persediaan',
            'pengajuan.saya'             => 'Pengajuan Saya',
            'peminjaman.index'           => 'Peminjaman',
            'barang.index'               => 'Persediaan & Stok',
            'pengajuan.index'            => 'Persetujuan Pengajuan Persediaan',
            'admin.manajemen-user.index' => 'Pengguna',
        ] as $route => $judul) {
            $this->actingAs($admin)->get(route($route))
                ->assertOk()
                ->assertSee("<title>" . e($judul) . " — BPS Admin</title>", false)
                ->assertSee('<span class="text-sm font-semibold">' . e($judul) . '</span>', false);
        }
    }

    public function test_judul_halaman_customer_sama_dengan_menu_sidebar(): void
    {
        $customer = $this->user('customer');

        foreach ([
            'customer.dashboard' => 'Dashboard',
            'barang.index'       => 'Katalog Persediaan',
            'pengajuan.index'    => 'Pengajuan Saya',
            'peminjaman.index'   => 'Peminjaman',
        ] as $route => $judul) {
            $this->actingAs($customer)->get(route($route))
                ->assertOk()
                ->assertSee("<title>" . e($judul) . " — BPS Pengguna</title>", false)
                ->assertSee('<span class="text-sm font-semibold">' . e($judul) . '</span>', false);
        }
    }

    public function test_tombol_keluar_hanya_di_menu_akun_top_bar(): void
    {
        foreach (['admin' => 'admin.dashboard', 'customer' => 'customer.dashboard'] as $role => $route) {
            $user = $this->user($role);

            $html = $this->actingAs($user)->get(route($route))->assertOk()->assertSee('aria-label="Menu akun"', false)->getContent();

            // Satu-satunya form logout ada di dalam menu akun (sidebar tidak lagi punya tombol Keluar)
            $this->assertSame(1, substr_count($html, 'action="' . route('logout') . '"'));
            $sidebar = substr($html, strpos($html, '<aside'), strpos($html, '</aside>') - strpos($html, '<aside'));
            $this->assertStringNotContainsString(route('logout'), $sidebar);

            $this->actingAs($user)->post(route('logout'))->assertRedirect();
            $this->assertGuest();
        }
    }

    public function test_label_peran_customer_tampil_sebagai_pengguna_dan_sidebar_tanpa_indikator_peran(): void
    {
        $customer = $this->user('customer');
        $admin = $this->user('admin');

        $this->assertSame('Pengguna', $customer->labelPeran());
        $this->assertSame('Admin', $admin->labelPeran());

        foreach ([[$customer, 'customer.dashboard'], [$admin, 'admin.dashboard']] as [$user, $route]) {
            $html = $this->actingAs($user)->get(route($route))->assertOk()->assertSee($user->labelPeran())->getContent();
            $sidebar = substr($html, strpos($html, '<aside'), strpos($html, '</aside>') - strpos($html, '<aside'));
            $this->assertStringNotContainsString('animate-pulse', $sidebar, 'indikator peran di sidebar sudah dihapus');
            $this->assertDoesNotMatchRegularExpression('/>\s*(Customer|customer|admin)\s*</', $html);
        }

        // Daftar pengguna (admin): badge & pilihan peran memakai label tampilan
        $this->actingAs($admin)->get(route('admin.manajemen-user.index'))
            ->assertSee('<option value="customer">Pengguna</option>', false)
            ->assertDontSee('>Customer<', false);
    }
}
