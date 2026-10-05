<?php

namespace Tests\Feature;

use App\Models\Barang;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

// Popularitas = jumlah pengajuan DISETUJUI (bukan unit); katalog memakai 90 hari terakhir
class PopularitasTest extends TestCase
{
    use RefreshDatabase;

    private User $pemohon;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pemohon = $this->user('customer');
    }

    private function user(string $role): User
    {
        $username = Str::lower(Str::random(8));
        $user = User::forceCreate(['name' => $username, 'username' => $username, 'email' => "$username@example.com", 'password' => 'x']);
        $user->assignRole($role);

        return $user;
    }

    private function barang(string $nama): Barang
    {
        return Barang::create(['nama_barang' => $nama, 'stock' => 1000, 'satuan' => 'BUAH']);
    }

    private function pengajuan(Barang $barang, string $status, int $jumlah = 1, ?string $tanggal = null): void
    {
        $order = Order::forceCreate([
            'user_id' => $this->pemohon->id, 'code' => Order::buatKode(), 'status' => $status,
            'created_at' => $tanggal ?? now(), 'updated_at' => $tanggal ?? now(),
        ]);
        $order->items()->create(['barang_id' => $barang->id, 'nama_barang' => $barang->nama_barang, 'satuan' => 'BUAH', 'jumlah' => $jumlah]);
    }

    private function data(array $params = [], string $role = 'customer'): \Illuminate\Support\Collection
    {
        $json = $this->actingAs($this->user($role))->getJson(route('barang.data', $params))->assertOk()->json('data');

        return collect($json)->keyBy('nama_barang');
    }

    public function test_hanya_pengajuan_disetujui_yang_dihitung_per_pengajuan_bukan_unit(): void
    {
        $amplop = $this->barang('Amplop');
        $this->pengajuan($amplop, 'disetujui', 2200);  // banyak unit tetap dihitung 1 pengajuan
        $this->pengajuan($amplop, 'disetujui', 5);
        $this->pengajuan($amplop, 'pending', 3);
        $this->pengajuan($amplop, 'ditolak', 3);
        $this->pengajuan($amplop, 'disetujui', 1, now()->subDays(120)->toDateTimeString()); // di luar 90 hari

        $item = $this->data()['Amplop'];

        $this->assertSame(2, $item['diminta_90_hari']);
        $this->assertSame(3, $item['diminta_total']);
    }

    public function test_urutan_terpopuler_dan_nama(): void
    {
        $jarang = $this->barang('Cutter');
        $sering = $this->barang('Binder');
        $this->barang('Amplop'); // belum pernah diminta
        $this->pengajuan($jarang, 'disetujui');
        foreach (range(1, 3) as $i) {
            $this->pengajuan($sering, 'disetujui');
        }
        $this->pengajuan($jarang, 'disetujui', 1, now()->subDays(200)->toDateTimeString()); // tidak dihitung di 90 hari

        $this->assertSame(['Binder', 'Cutter', 'Amplop'], $this->data(['urut' => 'populer'])->keys()->all());
        $this->assertSame(['Amplop', 'Binder', 'Cutter'], $this->data(['urut' => 'nama'])->keys()->all());

        $this->actingAs($this->user('customer'))->getJson(route('barang.data', ['urut' => 'acak']))->assertJsonValidationErrors('urut');
    }

    public function test_urutan_terpopuler_sepanjang_waktu_untuk_kelola_persediaan(): void
    {
        $baruSaja = $this->barang('Cutter');
        $dulu = $this->barang('Binder');
        $this->pengajuan($baruSaja, 'disetujui');
        foreach (range(1, 3) as $i) {
            $this->pengajuan($dulu, 'disetujui', 1, now()->subDays(200)->toDateTimeString());
        }

        // 90 hari: Cutter (1) di atas Binder (0); sepanjang waktu: Binder (3) di atas Cutter (1)
        $this->assertSame(['Cutter', 'Binder'], $this->data(['urut' => 'populer'], 'admin')->keys()->all());
        $this->assertSame(['Binder', 'Cutter'], $this->data(['urut' => 'populer_total'], 'admin')->keys()->all());
    }

    public function test_urutan_bawaan_katalog_dan_kelola_persediaan_terpopuler(): void
    {
        $this->actingAs($this->user('customer'))->get(route('barang.index'))
            ->assertViewHas('initial', fn ($i) => $i['urut'] === 'populer' && $i['urutBawaan'] === 'populer');
        $this->actingAs($this->user('admin'))->get(route('barang.katalog'))->assertViewHas('initial.urut', 'populer');
        $this->actingAs($this->user('admin'))->get(route('barang.index'))->assertViewHas('initial.urut', 'populer');
    }

    public function test_halaman_menampilkan_popularitas(): void
    {
        // Katalog: pilihan urutan (dari URL), lencana, dan jumlah diminta 90 hari
        $this->actingAs($this->user('customer'))->get(route('barang.index', ['urut' => 'populer']))
            ->assertOk()
            ->assertViewHas('initial.urut', 'populer')
            ->assertSee('<option value="populer">Terpopuler (90 hari terakhir)</option>', false)
            ->assertDontSee('· 90 hari</span>', false) // jendela waktu hanya disebut di pilihan urutan
            ->assertSee("x-text=\"ringkasJumlah(item.diminta_90_hari) + '×'\"", false)
            ->assertDontSee('Populer</span>', false); // hanya angka, tanpa lencana

        // Kelola persediaan: kolom Diminta (90 hari & sepanjang waktu)
        $this->actingAs($this->user('admin'))->get(route('barang.index'))
            ->assertOk()
            ->assertSee('>Diminta</th>', false)
            ->assertSee('· 90 hari terakhir</span>', false)
            ->assertSee('${item.diminta_total}× total', false)
            ->assertSee('<option value="populer">Terpopuler (90 hari terakhir)</option>', false)
            ->assertSee('<option value="populer_total">Terpopuler (total)</option>', false);
    }
}
