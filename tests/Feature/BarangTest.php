<?php

namespace Tests\Feature;

use App\Models\Barang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Tests\TestCase;

class BarangTest extends TestCase
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

    private function barang(int $stock = 10, string $nama = 'Pulpen', ?string $kode = null): Barang
    {
        return Barang::create(['stock_id' => $kode, 'nama_barang' => $nama, 'stock' => $stock, 'satuan' => 'BUAH']);
    }

    // ─── Satu halaman untuk dua peran, daftar lewat JSON ───

    public function test_admin_melihat_halaman_kelola_customer_melihat_katalog(): void
    {
        $this->actingAs($this->user('admin'))->get(route('barang.index'))
            ->assertOk()->assertViewIs('admin.barang.index')->assertViewHas('initial.dataUrl', route('barang.data'))->assertSee(route('barang.import'));

        $this->actingAs($this->user('customer'))->get(route('barang.index'))
            ->assertOk()->assertViewIs('customer.katalog.index')->assertViewHas('initial.dataUrl', route('barang.data'))->assertDontSee(route('barang.import'));
    }

    public function test_admin_bisa_membuka_katalog_dengan_layout_admin(): void
    {
        $this->actingAs($this->user('admin'))->get(route('barang.katalog'))
            ->assertOk()
            ->assertViewIs('customer.katalog.index')
            ->assertViewHas('initial.perPage', 12)
            ->assertSee('BPS Admin')
            ->assertSee('Katalog Persediaan</h2>', false)
            ->assertSee(route('barang.katalog'));

        // Menu sidebar admin: kelola & katalog
        $this->actingAs($this->user('admin'))->get(route('barang.index'))
            ->assertViewHas('initial.perPage', 10)
            ->assertSee(route('barang.katalog'));

        // Customer tetap mendapat katalog di kedua URL
        $this->actingAs($this->user('customer'))->get(route('barang.katalog'))->assertOk()->assertSee('BPS Pengguna');
    }

    public function test_data_json_mengikuti_ukuran_halaman_tampilan(): void
    {
        foreach (range(1, 15) as $i) {
            $this->barang(nama: "Barang $i");
        }

        $this->actingAs($this->user('admin'))->getJson(route('barang.data', ['per_page' => 12]))
            ->assertOk()->assertJsonCount(12, 'data');
        $this->actingAs($this->user('admin'))->getJson(route('barang.data', ['per_page' => 500]))
            ->assertJsonValidationErrors('per_page');
    }

    public function test_halaman_membawa_kondisi_awal_dari_query_string(): void
    {
        $this->actingAs($this->user('customer'))->get(route('barang.index', ['search' => ' stap ', 'stok_habis' => 1, 'page' => 3]))
            ->assertOk()
            ->assertViewHas('initial', fn (array $initial) => $initial['search'] === 'stap' && $initial['stokHabis'] === true && $initial['page'] === 3);
    }

    public function test_data_json_berisi_barang_dan_info_halaman(): void
    {
        $barang = $this->barang(stock: 5, nama: 'Stapler', kode: 'ATK-001');

        $this->actingAs($this->user('customer'))->getJson(route('barang.data'))
            ->assertOk()
            ->assertExactJson([
                'data' => [[
                    'id' => $barang->id, 'stock_id' => 'ATK-001', 'nama_barang' => 'Stapler',
                    'stock' => 5, 'dipesan' => 0, 'tersedia' => 5, 'satuan' => 'BUAH', 'foto_url' => $barang->foto_url, 'bisa_dihapus' => true,
                    'diminta_90_hari' => 0, 'diminta_total' => 0,
                ]],
                'meta' => ['current_page' => 1, 'last_page' => 1, 'from' => 1, 'to' => 1, 'total' => 1],
            ]);
    }

    public function test_data_json_per_halaman_admin_10_customer_12(): void
    {
        foreach (range(1, 15) as $i) {
            $this->barang(nama: "Barang $i");
        }

        $this->actingAs($this->user('admin'))->getJson(route('barang.data', ['page' => 2]))
            ->assertOk()->assertJsonCount(5, 'data')
            ->assertJsonPath('meta', ['current_page' => 2, 'last_page' => 2, 'from' => 11, 'to' => 15, 'total' => 15]);

        $this->actingAs($this->user('customer'))->getJson(route('barang.data'))
            ->assertOk()->assertJsonCount(12, 'data')->assertJsonPath('meta.last_page', 2);
    }

    public function test_pencarian_nama_barang(): void
    {
        $this->barang(nama: 'Stapler');
        $this->barang(nama: 'Amplop');

        $this->actingAs($this->user('customer'))->getJson(route('barang.data', ['search' => 'stap']))
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.nama_barang', 'Stapler');
    }

    public function test_barang_stok_habis_disembunyikan_secara_default(): void
    {
        $this->barang(stock: 5, nama: 'Stapler');
        $this->barang(stock: 0, nama: 'Amplop');

        foreach (['admin', 'customer'] as $role) {
            $this->actingAs($this->user($role))->getJson(route('barang.data'))
                ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.nama_barang', 'Stapler');

            $this->actingAs($this->user($role))->getJson(route('barang.data', ['stok_habis' => 1]))
                ->assertOk()->assertJsonCount(2, 'data');
        }
    }

    public function test_parameter_data_tidak_valid_ditolak(): void
    {
        $this->actingAs($this->user('customer'))->getJson(route('barang.data', ['page' => 0, 'stok_habis' => 'ya']))
            ->assertUnprocessable()->assertJsonValidationErrors(['page', 'stok_habis']);
    }

    public function test_tampilan_stok_habis_tidak_aktif(): void
    {
        // Customer: kartu abu-abu, tombol keranjang hanya untuk barang berstok, sisanya "Stok Habis" nonaktif
        $this->actingAs($this->user('customer'))->get(route('barang.index'))
            ->assertOk()
            ->assertSee("item.tersedia > 0 ? 'group-hover:scale-[1.06]' : 'grayscale'", false)
            ->assertSee('x-show="item.tersedia > 0"', false)
            ->assertSee('Stok Habis');

        // Admin: baris abu-abu bertanda "Habis", tombol ubah stok tetap aktif
        $this->actingAs($this->user('admin'))->get(route('barang.index'))
            ->assertOk()
            ->assertSee('[&>td:not(:last-child)]:opacity-50', false)
            ->assertSee('Habis')
            ->assertSee('openStockModal(item.id', false);
    }

    public function test_url_lama_diarahkan_ke_halaman_barang(): void
    {
        $admin = $this->user('admin');

        $this->actingAs($admin)->get('/admin/barang?search=x')->assertRedirect(route('barang.index', ['search' => 'x']));
        $this->actingAs($admin)->get('/customer/katalog')->assertRedirect(route('barang.index'));
    }

    public function test_tamu_dan_customer_tidak_bisa_mengubah_barang(): void
    {
        $barang = $this->barang();

        $this->get(route('barang.index'))->assertRedirect(route('login'));
        $this->getJson(route('barang.data'))->assertUnauthorized();

        $this->actingAs($this->user('customer'));
        $this->postJson(route('barang.store'), ['nama_barang' => 'X', 'stock' => 1, 'satuan' => 'BUAH'])->assertForbidden();
        $this->putJson(route('barang.update', $barang), ['nama_barang' => 'X', 'satuan' => 'BUAH'])->assertForbidden();
        $this->patchJson(route('barang.update-stock', $barang), ['operasi' => 'set', 'jumlah' => 0])->assertForbidden();
        $this->deleteJson(route('barang.destroy', $barang))->assertForbidden();
        $this->postJson(route('barang.import'))->assertForbidden();
        $this->getJson(route('barang.import.riwayat'))->assertForbidden();

        $this->assertSame(10, $barang->fresh()->stock);
    }

    // ─── Tambah & edit barang ───

    public function test_tambah_barang_baru_dengan_kode(): void
    {
        $this->actingAs($this->user('admin'))
            ->postJson(route('barang.store'), ['stock_id' => 'ATK-001', 'nama_barang' => 'Stapler', 'stock' => 5, 'satuan' => 'BUAH'])
            ->assertOk()->assertExactJson(['message' => 'Persediaan baru berhasil ditambahkan.']);

        $this->assertDatabaseHas('barang', ['stock_id' => 'ATK-001', 'nama_barang' => 'Stapler', 'stock' => 5]);
    }

    public function test_tambah_barang_yang_sudah_ada_menambah_stok_dan_melengkapi_kode(): void
    {
        $barang = $this->barang(stock: 3, nama: 'Stapler');

        $this->actingAs($this->user('admin'))
            ->postJson(route('barang.store'), ['stock_id' => 'ATK-001', 'nama_barang' => 'stapler', 'stock' => 2, 'satuan' => 'BUAH'])
            ->assertOk()->assertExactJson(['message' => "Stok persediaan 'Stapler' berhasil ditambahkan."]);

        $this->assertSame(1, Barang::count());
        $this->assertSame([5, 'ATK-001'], [$barang->fresh()->stock, $barang->fresh()->stock_id]);
    }

    public function test_edit_barang_mengubah_kode_nama_satuan_tanpa_mengubah_stok(): void
    {
        $barang = $this->barang(stock: 7);

        $this->actingAs($this->user('admin'))
            ->putJson(route('barang.update', $barang), ['stock_id' => 'ATK-009', 'nama_barang' => 'Pulpen Biru', 'satuan' => 'PAK', 'stock' => 999])
            ->assertOk()->assertExactJson(['message' => 'Persediaan berhasil diperbarui.']);

        $barang->refresh();
        $this->assertSame(['ATK-009', 'Pulpen Biru', 'PAK', 7], [$barang->stock_id, $barang->nama_barang, $barang->satuan, $barang->stock]);
    }

    public function test_kode_barang_harus_unik(): void
    {
        $this->barang(nama: 'Stapler', kode: 'ATK-001');
        $barang = $this->barang(nama: 'Amplop', kode: 'ATK-002');

        $this->actingAs($this->user('admin'))
            ->putJson(route('barang.update', $barang), ['stock_id' => 'ATK-001', 'nama_barang' => 'Amplop', 'satuan' => 'BUAH'])
            ->assertJsonValidationErrors('stock_id');

        // Menyimpan ulang dengan kode sendiri tetap boleh
        $this->actingAs($this->user('admin'))
            ->putJson(route('barang.update', $barang), ['stock_id' => 'ATK-002', 'nama_barang' => 'Amplop Besar', 'satuan' => 'BUAH'])
            ->assertOk();
    }

    // ─── Ubah stok ───

    public function test_ubah_stok_tambah_kurang_dan_atur(): void
    {
        $barang = $this->barang(stock: 10);
        $this->actingAs($this->user('admin'));

        $this->patchJson(route('barang.update-stock', $barang), ['operasi' => 'tambah', 'jumlah' => 5])
            ->assertOk()->assertExactJson(['message' => "Stok 'Pulpen' diubah: 10 → 15.", 'warning' => null]);
        $this->assertSame(15, $barang->fresh()->stock);

        $this->patchJson(route('barang.update-stock', $barang), ['operasi' => 'kurang', 'jumlah' => 4])->assertOk();
        $this->assertSame(11, $barang->fresh()->stock);

        $this->patchJson(route('barang.update-stock', $barang), ['operasi' => 'set', 'jumlah' => 0])->assertOk();
        $this->assertSame(0, $barang->fresh()->stock);
    }

    public function test_stok_tidak_bisa_kurang_dari_nol(): void
    {
        $barang = $this->barang(stock: 3);

        $this->actingAs($this->user('admin'))
            ->patchJson(route('barang.update-stock', $barang), ['operasi' => 'kurang', 'jumlah' => 4])
            ->assertUnprocessable()->assertExactJson(['message' => "Stok 'Pulpen' tidak boleh kurang dari 0. Sisa stok: 3."]);

        $this->assertSame(3, $barang->fresh()->stock);
    }

    public function test_jumlah_ubah_stok_tidak_valid_ditolak(): void
    {
        $barang = $this->barang(stock: 3);
        $this->actingAs($this->user('admin'));

        $this->patchJson(route('barang.update-stock', $barang), ['operasi' => 'tambah', 'jumlah' => 0])->assertJsonValidationErrors('jumlah');
        $this->patchJson(route('barang.update-stock', $barang), ['operasi' => 'set', 'jumlah' => -1])->assertJsonValidationErrors('jumlah');
        $this->patchJson(route('barang.update-stock', $barang), ['operasi' => 'kali', 'jumlah' => 2])->assertJsonValidationErrors('operasi');

        $this->assertSame(3, $barang->fresh()->stock);
    }

    // ─── Hapus & impor Excel ───

    public function test_hapus_barang(): void
    {
        $barang = $this->barang();

        $this->actingAs($this->user('admin'))->deleteJson(route('barang.destroy', $barang))
            ->assertOk()->assertExactJson(['message' => "Persediaan 'Pulpen' berhasil dihapus."]);

        $this->assertModelMissing($barang);
    }

    public function test_data_json_menandai_persediaan_yang_bisa_dihapus(): void
    {
        $belumDiajukan = $this->barang(nama: 'Amplop');
        $pernahDiajukan = $this->barang(nama: 'Stapler');
        $order = \App\Models\Order::forceCreate(['user_id' => $this->user('customer')->id, 'code' => 'REQ-X', 'status' => 'ditolak']);
        $order->items()->create(['barang_id' => $pernahDiajukan->id, 'nama_barang' => 'Stapler', 'satuan' => 'BUAH', 'jumlah' => 1]);

        $data = collect($this->actingAs($this->user('admin'))->getJson(route('barang.data'))->assertOk()->json('data'))->keyBy('nama_barang');

        $this->assertTrue($data['Amplop']['bisa_dihapus']);
        $this->assertFalse($data['Stapler']['bisa_dihapus']);
    }
}
