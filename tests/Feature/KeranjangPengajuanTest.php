<?php

namespace Tests\Feature;

use App\Models\Barang;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class KeranjangPengajuanTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role = 'customer'): User
    {
        $username = Str::lower(Str::random(8));
        $user = User::forceCreate([
            'name' => $username, 'username' => $username, 'email' => "$username@example.com", 'password' => 'password',
        ]);
        $user->assignRole($role);

        return $user;
    }

    private function barang(int $stock = 10, string $nama = 'Pulpen'): Barang
    {
        return Barang::create(['stock_id' => Str::upper(Str::random(8)), 'nama_barang' => $nama, 'stock' => $stock, 'satuan' => 'BUAH']);
    }

    private function orderUntuk(User $user, Barang $barang, int $jumlah): Order
    {
        $user->cartItems()->create(['barang_id' => $barang->id, 'jumlah' => $jumlah]);
        $this->actingAs($user)->postJson(route('pengajuan.store'))->assertOk();
        // Di browser pesan sukses (flash) langsung terpakai di halaman Pengajuan Saya; jangan bocor ke request uji berikutnya
        $this->flushSession();

        return $user->orders()->latest()->firstOrFail();
    }

    // ─── Keranjang ───

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->post(route('keranjang.add', $this->barang()), ['jumlah' => 1])->assertRedirect(route('login'));
    }

    public function test_menambah_barang_yang_sama_menjumlahkan_dalam_satu_baris(): void
    {
        $user = $this->user();
        $barang = $this->barang();

        $this->actingAs($user)->postJson(route('keranjang.add', $barang), ['jumlah' => 2])
            ->assertOk()->assertJson(['jumlah_keranjang' => 1]);
        $this->actingAs($user)->postJson(route('keranjang.add', $barang), ['jumlah' => 3])
            ->assertOk()->assertJson(['message' => "'Pulpen' ditambahkan ke keranjang (5 BUAH).", 'jumlah_keranjang' => 1]);

        $this->assertSame(1, $user->cartItems()->count());
        $this->assertSame(5, $user->cartItems()->first()->jumlah);
    }

    public function test_tidak_bisa_menambah_melebihi_stok(): void
    {
        $user = $this->user();
        $barang = $this->barang(stock: 3);

        $this->actingAs($user)->postJson(route('keranjang.add', $barang), ['jumlah' => 2]);
        $this->actingAs($user)->postJson(route('keranjang.add', $barang), ['jumlah' => 2])
            ->assertUnprocessable()->assertJson(['message' => "Stok 'Pulpen' tidak mencukupi. Tersedia: 3 BUAH, karena di keranjang sudah ada 2."]);

        $this->assertSame(2, $user->cartItems()->first()->jumlah);
    }

    public function test_jumlah_tidak_valid_ditolak(): void
    {
        $user = $this->user();
        $barang = $this->barang();

        $this->actingAs($user)->postJson(route('keranjang.add', $barang), ['jumlah' => 0])->assertJsonValidationErrors('jumlah');
        $this->actingAs($user)->postJson(route('keranjang.add', $barang), ['jumlah' => -5])->assertJsonValidationErrors('jumlah');

        $item = $user->cartItems()->create(['barang_id' => $barang->id, 'jumlah' => 1]);
        $this->actingAs($user)->patchJson(route('keranjang.update', $item), ['jumlah' => 11])->assertUnprocessable();
        $this->actingAs($user)->patchJson(route('keranjang.update', $item), ['jumlah' => 0])->assertJsonValidationErrors('jumlah');
        $this->assertSame(1, $item->fresh()->jumlah);
    }

    public function test_tidak_bisa_mengubah_atau_menghapus_keranjang_orang_lain(): void
    {
        $pemilik = $this->user();
        $item = $pemilik->cartItems()->create(['barang_id' => $this->barang()->id, 'jumlah' => 1]);
        $lain = $this->user();

        $this->actingAs($lain)->patchJson(route('keranjang.update', $item), ['jumlah' => 2])->assertForbidden();
        $this->actingAs($lain)->deleteJson(route('keranjang.delete', $item))->assertForbidden();
        $this->assertModelExists($item);

        $this->actingAs($pemilik)->deleteJson(route('keranjang.delete', $item))
            ->assertOk()->assertExactJson(['message' => 'Persediaan dihapus dari keranjang.', 'jumlah_keranjang' => 0]);
        $this->assertModelMissing($item);
    }

    public function test_keranjang_menandai_barang_yang_melebihi_stok_tersedia_tanpa_mengubahnya(): void
    {
        $user = $this->user();
        $berkurang = $this->barang(stock: 10, nama: 'Kertas');
        $habis = $this->barang(stock: 10, nama: 'Tinta');
        $kurang = $user->cartItems()->create(['barang_id' => $berkurang->id, 'jumlah' => 8]);
        $kosong = $user->cartItems()->create(['barang_id' => $habis->id, 'jumlah' => 2]);
        $berkurang->update(['stock' => 5]);
        $habis->update(['stock' => 0]);

        // Data awal halaman membawa stok tersedia tiap baris; peringatan & tombol kirim nonaktif dihitung di halaman
        $this->actingAs($user)->get(route('keranjang.index'))
            ->assertOk()
            ->assertViewHas('items', fn ($items) => collect($items)->map(fn ($i) => [$i['nama_barang'], $i['jumlah'], $i['tersedia']])->sort()->values()->all()
                === [['Kertas', 8, 5], ['Tinta', 2, 0]])
            ->assertSee('Kurangi jumlahnya menjadi ${item.tersedia} atau kurang.', false)
            ->assertSee('Perbaiki ${jumlahBermasalah} item yang ditandai di atas untuk mengirim pengajuan.', false)
            ->assertSee(':disabled="jumlahBermasalah > 0', false);

        // Keranjang tidak diubah otomatis
        $this->assertSame(8, $kurang->fresh()->jumlah);
        $this->assertModelExists($kosong);
    }

    public function test_ubah_jumlah_melebihi_stok_tersedia_ditolak_dengan_data_baris_terbaru(): void
    {
        $user = $this->user();
        $barang = $this->barang(stock: 5, nama: 'Kertas');
        $item = $user->cartItems()->create(['barang_id' => $barang->id, 'jumlah' => 2]);
        $this->orderUntuk($this->user(), $barang, 2);

        $this->actingAs($user)->patchJson(route('keranjang.update', $item), ['jumlah' => 4])
            ->assertUnprocessable()
            ->assertJson([
                'message' => "Stok 'Kertas' tidak mencukupi. Tersedia: 3 BUAH.",
                'item'    => ['id' => $item->id, 'jumlah' => 2, 'tersedia' => 3, 'dipesan' => 2],
            ]);
        $this->assertSame(2, $item->fresh()->jumlah);

        $this->actingAs($user)->patchJson(route('keranjang.update', $item), ['jumlah' => 3])
            ->assertOk()
            ->assertJson(['message' => 'Jumlah persediaan berhasil diperbarui!', 'item' => ['jumlah' => 3, 'tersedia' => 3]]);
        $this->assertSame(3, $item->fresh()->jumlah);
    }    public function test_ikon_keranjang_di_top_bar_menghitung_isi_keranjang(): void
    {
        $user = $this->user();
        $user->cartItems()->create(['barang_id' => $this->barang()->id, 'jumlah' => 1]);
        $user->cartItems()->create(['barang_id' => $this->barang(stock: 0, nama: 'Tinta')->id, 'jumlah' => 1]);

        $this->actingAs($user)->get(route('customer.dashboard'))
            ->assertOk()
            ->assertSee('x-data="{ jumlah: 2 }"', false)
            ->assertSee(route('keranjang.index'));
    }
    public function test_jumlah_keranjang_dihitung_per_barang(): void
    {
        $user = $this->user();
        $this->actingAs($user)->postJson(route('keranjang.add', $this->barang(nama: 'Pulpen')), ['jumlah' => 2]);
        $this->actingAs($user)->postJson(route('keranjang.add', $this->barang(nama: 'Spidol')), ['jumlah' => 1])
            ->assertJson(['jumlah_keranjang' => 2]);
    }
    public function test_tombol_lihat_keranjang_di_katalog_menampilkan_jumlah(): void
    {
        $user = $this->user();
        $user->cartItems()->create(['barang_id' => $this->barang()->id, 'jumlah' => 1]);

        // Badge tombol & ikon top bar mulai dari jumlah yang sama, lalu diperbarui lewat event setelah tambah barang
        $this->actingAs($user)->get(route('barang.index'))
            ->assertOk()
            ->assertSee('x-data="{ jumlah: 1 }" @keranjang-diperbarui.window', false)
            ->assertSee("new CustomEvent('keranjang-diperbarui'", false);
    }

    public function test_halaman_keranjang_dan_riwayat_terpisah(): void
    {
        $user = $this->user();
        $user->cartItems()->create(['barang_id' => $this->barang(nama: 'Spidol')->id, 'jumlah' => 2]);
        $order = $this->orderUntuk($user, $this->barang(nama: 'Map Plastik'), 1);
        $user->cartItems()->create(['barang_id' => $this->barang(nama: 'Lakban')->id, 'jumlah' => 2]);

        $this->actingAs($user)->get(route('keranjang.index'))
            ->assertOk()->assertSee('Lakban')->assertViewHas('items', fn ($items) => collect($items)->pluck('nama_barang')->all() === ['Lakban'])->assertDontSee($order->code);

        $this->actingAs($user)->get(route('pengajuan.index'))
            ->assertOk()->assertSee($order->code)->assertDontSee('Lakban');
    }

    // ─── Checkout ───

    public function test_checkout_membuat_pengajuan_dengan_salinan_barang_dan_mengosongkan_keranjang(): void
    {
        $user = $this->user();
        $barang = $this->barang(stock: 10, nama: 'Pulpen');
        $user->cartItems()->create(['barang_id' => $barang->id, 'jumlah' => 4]);

        $response = $this->actingAs($user)->postJson(route('pengajuan.store'));

        $order = $user->orders()->sole();
        $response->assertOk()->assertExactJson([
            'message' => "Pengajuan {$order->code} berhasil dikirim!", 'code' => $order->code,
            'url' => route('pengajuan.index'), 'jumlah_keranjang' => 0,
        ]);

        // Halaman keranjang pindah ke url (Pengajuan Saya); pesan sukses tampil di sana sekali saja
        $this->actingAs($user)->get(route('pengajuan.index'))->assertSee("Pengajuan {$order->code} berhasil dikirim!");
        $this->actingAs($user)->get(route('pengajuan.index'))->assertDontSee('berhasil dikirim!');
        $this->assertSame('pending', $order->fresh()->status);
        $this->assertSame(0, $user->cartItems()->count());
        $this->assertSame(10, $barang->fresh()->stock, 'stok baru dipotong saat disetujui');

        // Kode & nama barang diubah setelah checkout: pengajuan tetap memakai salinan lama
        $kodeLama = $barang->stock_id;
        $barang->update(['stock_id' => 'KODE-BARU', 'nama_barang' => 'Pulpen Baru']);
        $item = $order->items()->sole();
        $this->assertSame([$kodeLama, 'Pulpen', 'BUAH', 4], [$item->stock_id, $item->nama_barang, $item->satuan, $item->jumlah]);
    }

    public function test_checkout_keranjang_kosong_tidak_membuat_pengajuan(): void
    {
        $user = $this->user();

        $this->actingAs($user)->postJson(route('pengajuan.store'))
            ->assertUnprocessable()->assertExactJson(['message' => 'Keranjang kamu masih kosong!', 'items' => []]);

        $this->assertSame(0, Order::count());
    }

    public function test_checkout_ditolak_jika_ada_barang_melebihi_stok_tersedia(): void
    {
        $user = $this->user();
        $barang = $this->barang(stock: 10, nama: 'Kertas');
        $item = $user->cartItems()->create(['barang_id' => $barang->id, 'jumlah' => 8]);
        $barang->update(['stock' => 5]);

        // Ditolak beserta stok tersedia terbaru tiap baris, agar peringatan di halaman langsung tampil
        $this->actingAs($user)->postJson(route('pengajuan.store'))
            ->assertUnprocessable()
            ->assertJson([
                'message' => 'Stok tidak mencukupi untuk: Kertas (tersedia 5). Kurangi jumlah atau hapus persediaannya, lalu kirim ulang.',
                'items'   => [['id' => $item->id, 'jumlah' => 8, 'tersedia' => 5]],
            ]);

        $this->assertSame(0, Order::count());
        $this->assertSame(8, $item->fresh()->jumlah);
    }
    public function test_pdf_pengajuan_orang_lain_404(): void
    {
        $order = $this->orderUntuk($this->user(), $this->barang(), 1);
        $order->update(['status' => 'disetujui']);

        $this->actingAs($this->user())->get(route('pengajuan.cetak-pdf', $order))->assertNotFound();
        $this->actingAs($order->user)->get(route('pengajuan.cetak-pdf', $order))->assertOk();
    }

    public function test_riwayat_customer_tampil(): void
    {
        $user = $this->user();
        $order = $this->orderUntuk($user, $this->barang(nama: 'Map Plastik'), 2);

        $this->actingAs($user)->get(route('pengajuan.index'))
            ->assertOk()->assertSee($order->code)->assertSee('Map Plastik');
    }

    // ─── Reservasi stok oleh pengajuan pending ───

    public function test_stok_1_dua_user_hanya_yang_pertama_checkout_yang_berhasil(): void
    {
        $barang = $this->barang(stock: 1, nama: 'Stapler');
        [$a, $b] = [$this->user(), $this->user()];

        // Keranjang tidak memesan stok: keduanya boleh menambahkan
        $this->actingAs($a)->postJson(route('keranjang.add', $barang), ['jumlah' => 1])->assertOk();
        $this->actingAs($b)->postJson(route('keranjang.add', $barang), ['jumlah' => 1])->assertOk();

        $this->actingAs($a)->postJson(route('pengajuan.store'))->assertOk();
        $this->actingAs($b)->postJson(route('pengajuan.store'))
            ->assertUnprocessable()->assertJson(['message' => 'Stok tidak mencukupi untuk: Stapler (tersedia 0). Kurangi jumlah atau hapus persediaannya, lalu kirim ulang.', 'items' => [['tersedia' => 0, 'dipesan' => 1]]]);

        $this->assertSame(1, Order::count());
        $this->assertSame(1, $b->cartItems()->count(), 'barang tetap di keranjang B');
        $this->assertSame(1, $barang->fresh()->stock, 'stok fisik baru dipotong saat disetujui');

        // B melihat barang ditandai di keranjang, dan katalog menyembunyikannya
        $this->actingAs($b)->get(route('keranjang.index'))
            ->assertSee('Stok habis, semua sudah diajukan oleh tim lain.');
        $this->actingAs($b)->getJson(route('barang.data'))->assertJsonCount(0, 'data');
        $this->actingAs($b)->getJson(route('barang.data', ['stok_habis' => 1]))
            ->assertJsonPath('data.0.dipesan', 1)->assertJsonPath('data.0.tersedia', 0);
    }

    public function test_menambah_ke_keranjang_dibatasi_stok_tersedia(): void
    {
        $barang = $this->barang(stock: 3, nama: 'Stapler');
        $this->orderUntuk($this->user(), $barang, 2);

        $user = $this->user();
        $this->actingAs($user)->postJson(route('keranjang.add', $barang), ['jumlah' => 2])
            ->assertUnprocessable()->assertJson(['message' => "Stok 'Stapler' tidak mencukupi. Tersedia: 1 BUAH, karena di keranjang sudah ada 0."]);
        $this->actingAs($user)->postJson(route('keranjang.add', $barang), ['jumlah' => 1])->assertOk();
    }

    public function test_pengajuan_ditolak_membebaskan_stok_untuk_user_lain(): void
    {
        $barang = $this->barang(stock: 1, nama: 'Stapler');
        $order = $this->orderUntuk($this->user(), $barang, 1);
        $b = $this->user();
        $b->cartItems()->create(['barang_id' => $barang->id, 'jumlah' => 1]);

        $this->actingAs($b)->postJson(route('pengajuan.store'))->assertUnprocessable();

        $this->actingAs($this->user('admin'))->patch(route('pengajuan.update-status', $order), ['status' => 'ditolak', 'alasan' => 'Tidak perlu']);

        $this->actingAs($b)->postJson(route('pengajuan.store'))->assertOk();
        $this->assertSame(1, $b->orders()->count());
    }

    public function test_menyetujui_memotong_stok_dan_melepas_reservasi(): void
    {
        $barang = $this->barang(stock: 10);
        $order = $this->orderUntuk($this->user(), $barang, 4);

        $this->assertSame(6, Barang::withDipesan()->find($barang->id)->tersedia);

        $this->actingAs($this->user('admin'))->patch(route('pengajuan.update-status', $order), ['status' => 'disetujui']);

        $barang = Barang::withDipesan()->find($barang->id);
        $this->assertSame([6, 0, 6], [$barang->stock, (int) $barang->dipesan, $barang->tersedia]);
    }

    public function test_admin_mengurangi_stok_di_bawah_yang_diajukan_tetap_disimpan_dengan_peringatan(): void
    {
        $barang = $this->barang(stock: 10, nama: 'Stapler');
        $this->orderUntuk($this->user(), $barang, 8);

        $this->actingAs($this->user('admin'))
            ->patchJson(route('barang.update-stock', $barang), ['operasi' => 'set', 'jumlah' => 6])
            ->assertOk()
            ->assertJsonPath('message', "Stok 'Stapler' diubah: 10 → 6.")
            ->assertJsonPath('warning', 'Perhatian: 8 BUAH sudah diajukan di pengajuan pending, lebih dari stok baru. Sebagian pengajuan tidak akan bisa disetujui.');

        $this->assertSame(6, $barang->fresh()->stock);

        // Tanpa pengajuan yang terlampaui: tidak ada peringatan
        $this->actingAs($this->user('admin'))
            ->patchJson(route('barang.update-stock', $barang), ['operasi' => 'tambah', 'jumlah' => 10])
            ->assertJsonPath('warning', null);
    }

    // ─── Persetujuan admin ───

    public function test_menyetujui_memotong_stok_dan_mencatat_riwayat(): void
    {
        $barang = $this->barang(stock: 10);
        $order = $this->orderUntuk($this->user(), $barang, 4);
        $admin = $this->user('admin');

        $this->actingAs($admin)->patch(route('pengajuan.update-status', $order), ['status' => 'disetujui'])
            ->assertSessionHas('success');

        $this->assertSame('disetujui', $order->fresh()->status);
        $this->assertSame(6, $barang->fresh()->stock);
        $this->assertDatabaseHas('riwayat', [
            'order_id' => $order->id, 'actor_id' => $admin->id, 'status_sebelumnya' => 'pending', 'status_sesudah' => 'disetujui',
        ]);
    }

    public function test_menyetujui_dengan_stok_kurang_ditolak_tanpa_perubahan(): void
    {
        $barang = $this->barang(stock: 10);
        $order = $this->orderUntuk($this->user(), $barang, 4);
        $barang->update(['stock' => 3]);

        $this->actingAs($this->user('admin'))->patch(route('pengajuan.update-status', $order), ['status' => 'disetujui'])
            ->assertSessionHas('error');

        $this->assertSame('pending', $order->fresh()->status);
        $this->assertSame(3, $barang->fresh()->stock);
        $this->assertDatabaseCount('riwayat', 0);
    }

    public function test_pengajuan_yang_sudah_diproses_tidak_memotong_stok_dua_kali(): void
    {
        $barang = $this->barang(stock: 10);
        $order = $this->orderUntuk($this->user(), $barang, 4);
        $admin = $this->user('admin');

        $this->actingAs($admin)->patch(route('pengajuan.update-status', $order), ['status' => 'disetujui']);
        $this->actingAs($admin)->patch(route('pengajuan.update-status', $order), ['status' => 'disetujui'])
            ->assertSessionHas('error');

        $this->assertSame(6, $barang->fresh()->stock);
    }

    public function test_menolak_tidak_mengubah_stok(): void
    {
        $barang = $this->barang(stock: 10);
        $order = $this->orderUntuk($this->user(), $barang, 4);

        $this->actingAs($this->user('admin'))->patch(route('pengajuan.update-status', $order), ['status' => 'ditolak', 'alasan' => 'Habis']);

        $this->assertSame(['ditolak', 'Habis'], [$order->fresh()->status, $order->fresh()->alasan]);
        $this->assertSame(10, $barang->fresh()->stock);
    }

    public function test_persediaan_yang_pernah_diajukan_tidak_bisa_dihapus_apa_pun_statusnya(): void
    {
        $admin = $this->user('admin');

        foreach (['pending', 'disetujui', 'ditolak'] as $status) {
            $barang = $this->barang(nama: "Stapler $status");
            $order = $this->orderUntuk($this->user(), $barang, 1);
            $order->update(['status' => $status]);

            $this->actingAs($admin)->deleteJson(route('barang.destroy', $barang))
                ->assertUnprocessable()
                ->assertExactJson(['message' => "Persediaan 'Stapler $status' tidak bisa dihapus karena sudah diajukan di 1 pengajuan."]);

            $this->assertModelExists($barang);
            $this->assertSame($barang->id, $order->items()->sole()->barang_id);
        }
    }

    public function test_halaman_admin_transaksi_dan_pdf(): void
    {
        $order = $this->orderUntuk($this->user(), $this->barang(nama: 'Amplop'), 1);
        $admin = $this->user('admin');

        $this->actingAs($admin)->get(route('pengajuan.index'))->assertOk()->assertSee($order->code)->assertSee('Amplop');

        $order->update(['status' => 'disetujui']);
        $this->actingAs($admin)->get(route('pengajuan.cetak-pdf', $order))->assertOk();

        $pdf = view('pdf.bukti-pengajuan', ['pengajuan' => $order->load('items', 'user'), 'nomorSurat' => $order->nomorSurat()])->render();
        $this->assertStringContainsString($order->items->first()->stock_id, $pdf);
    }

    public function test_satu_halaman_pengajuan_untuk_admin_dan_customer(): void
    {
        $milikku = $this->orderUntuk($pemilik = $this->user(), $this->barang(nama: 'Amplop'), 1);
        $milikLain = $this->orderUntuk($this->user(), $this->barang(nama: 'Stapler'), 1);

        // Admin: semua pengajuan
        $this->actingAs($this->user('admin'))->get(route('pengajuan.index'))
            ->assertOk()->assertViewIs('admin.transaksi.index')->assertSee($milikku->code)->assertSee($milikLain->code);

        // Customer: hanya miliknya
        $this->actingAs($pemilik)->get(route('pengajuan.index'))
            ->assertOk()->assertViewIs('customer.pengajuan.index')->assertSee($milikku->code)->assertDontSee($milikLain->code);
    }

    public function test_url_lama_pengajuan_diarahkan(): void
    {
        $this->actingAs($this->user('admin'))->get('/admin/transaksi?page=2')->assertRedirect(route('pengajuan.index', ['page' => 2]));
        $this->actingAs($this->user())->get('/customer/pengajuan')->assertRedirect(route('pengajuan.index'));
        $this->actingAs($this->user())->get('/customer/riwayat-pengajuan')->assertRedirect(route('pengajuan.index'));
    }

    public function test_tamu_diarahkan_ke_login_dari_pengajuan_dan_keranjang(): void
    {
        $this->get(route('pengajuan.index'))->assertRedirect(route('login'));
        $this->get(route('keranjang.index'))->assertRedirect(route('login'));
        $this->actingAs($this->user())->get('/customer/keranjang')->assertRedirect(route('keranjang.index'));
    }

    // ─── Pengajuan oleh admin ───

    public function test_pengajuan_admin_langsung_disetujui_dan_memotong_stok(): void
    {
        $admin = $this->user('admin');
        $barang = $this->barang(stock: 10, nama: 'Stapler');

        $this->actingAs($admin)->postJson(route('keranjang.add', $barang), ['jumlah' => 4])
            ->assertOk()->assertJson(['jumlah_keranjang' => 1]);

        $response = $this->actingAs($admin)->postJson(route('pengajuan.store'));

        $order = $admin->orders()->sole();
        $response->assertOk()->assertJson([
            'message' => "Pengajuan {$order->code} disetujui dan stok telah dipotong.", 'url' => route('pengajuan.saya'),
        ]);
        $this->actingAs($admin)->get(route('pengajuan.saya'))->assertSee("Pengajuan {$order->code} disetujui dan stok telah dipotong.");

        $this->assertSame('disetujui', $order->status);
        $this->assertSame(6, $barang->fresh()->stock);
        $this->assertSame(0, $admin->cartItems()->count());
        $this->assertDatabaseHas('riwayat', [
            'order_id' => $order->id, 'actor_id' => $admin->id, 'status_sebelumnya' => null, 'status_sesudah' => 'disetujui',
        ]);

        // Langsung bisa dicetak
        $this->actingAs($admin)->get(route('pengajuan.cetak-pdf', $order))->assertOk();
    }

    public function test_pengajuan_saya_admin_hanya_menampilkan_miliknya_sendiri(): void
    {
        $admin = $this->user('admin');
        $milikAdmin = $this->orderUntuk($admin, $this->barang(nama: 'Amplop'), 1);
        $milikCustomer = $this->orderUntuk($this->user(), $this->barang(nama: 'Stapler'), 1);

        $this->actingAs($admin)->get(route('pengajuan.saya'))
            ->assertOk()
            ->assertViewIs('customer.pengajuan.index')
            ->assertSee('BPS Admin')
            ->assertSee($milikAdmin->code)
            ->assertDontSee($milikCustomer->code);

        // Menu sidebar admin mengarah ke halaman ini
        $this->actingAs($admin)->get(route('barang.katalog'))->assertSee(route('pengajuan.saya'));
    }

    public function test_pengajuan_admin_tidak_boleh_memakai_stok_yang_sudah_diajukan_customer(): void
    {
        $barang = $this->barang(stock: 1, nama: 'Stapler');
        $this->orderUntuk($this->user(), $barang, 1);
        $admin = $this->user('admin');
        $admin->cartItems()->create(['barang_id' => $barang->id, 'jumlah' => 1]);

        $this->actingAs($admin)->postJson(route('pengajuan.store'))
            ->assertUnprocessable()->assertJson(['message' => 'Stok tidak mencukupi untuk: Stapler (tersedia 0). Kurangi jumlah atau hapus persediaannya, lalu kirim ulang.']);

        $this->assertSame(0, $admin->orders()->count());
        $this->assertSame(1, $barang->fresh()->stock);
    }

    public function test_admin_menambah_ke_keranjang_dari_katalog_bukan_dari_halaman_kelola(): void
    {
        $admin = $this->user('admin');
        $admin->cartItems()->create(['barang_id' => $this->barang(nama: 'Amplop')->id, 'jumlah' => 2]);

        // Halaman kelola persediaan: tanpa tombol tambah ke keranjang, ikon keranjang top bar tetap ada
        $this->actingAs($admin)->get(route('barang.index'))
            ->assertOk()
            ->assertDontSee('openAjukanModal', false)
            ->assertDontSee('id="modalAjukan"', false)
            ->assertSee('x-data="{ jumlah: 1 }"', false);

        $this->actingAs($admin)->get(route('barang.katalog'))
            ->assertOk()
            ->assertSee('openAjukanModal(item.id, item.nama_barang, item.tersedia', false)
            ->assertSee('id="modalAjukan"', false);

        $this->actingAs($admin)->get(route('keranjang.index'))
            ->assertOk()
            ->assertSee('BPS Admin')
            ->assertSee('Amplop')
            ->assertSee('Pengajuan admin langsung disetujui dan stok langsung dipotong.');
    }

    public function test_customer_tidak_bisa_menyetujui(): void
    {
        $order = $this->orderUntuk($this->user(), $this->barang(), 1);

        $this->actingAs($order->user)->patch(route('pengajuan.update-status', $order), ['status' => 'disetujui'])->assertForbidden();
    }
}
