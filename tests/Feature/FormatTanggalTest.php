<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

// Semua tanggal tampil dalam format Indonesia: 1 Januari 2026 (dengan jam: 1 Januari 2026, 10:05)
class FormatTanggalTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_format_tanggal_indonesia(): void
    {
        $this->assertSame('1 Januari 2026', Carbon::parse('2026-01-01 10:05')->tanggal());
        $this->assertSame('17 Agustus 2026, 14:30', Carbon::parse('2026-08-17 14:30')->tanggalJam());
    }

    public function test_tanggal_di_halaman_pengajuan_dan_persetujuan(): void
    {
        Carbon::setTestNow('2026-01-01 10:05');

        $username = Str::lower(Str::random(8));
        $customer = User::forceCreate(['name' => $username, 'username' => $username, 'email' => "$username@example.com", 'password' => 'x']);
        $customer->assignRole('customer');
        $customer->orders()->create(['code' => Order::buatKode()]);

        $admin = User::forceCreate(['name' => 'adm', 'username' => 'adm', 'email' => 'adm@example.com', 'password' => 'x']);
        $admin->assignRole('admin');

        $this->actingAs($customer)->get(route('pengajuan.index'))->assertSee('1 Januari 2026, 10:05');
        $this->actingAs($admin)->get(route('pengajuan.index'))->assertSee('1 Januari 2026, 10:05');
        $this->actingAs($admin)->get(route('admin.manajemen-user.index'))->assertSee('1 Januari 2026');
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertSee('Kamis, 1 Januari 2026');
    }
}
