<?php

namespace Tests\Feature;

use App\Models\Barang;
use App\Models\Order;
use App\Models\Team;
use App\Models\User;
use App\Models\WhatsappMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

// Persetujuan cepat tanpa login lewat tautan bertanda tangan di pesan WhatsApp (ketua tim & admin)
class PersetujuanCepatTest extends TestCase
{
    use RefreshDatabase;

    private const TTD = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

    private User $staf;
    private User $ketua;
    private User $admin;
    private Team $tim;
    private Barang $barang;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Http::preventStrayRequests();
        Http::fake(['whatsapp.test/*' => Http::response(['status' => 'ok'], 201)]);

        $this->staf = $this->user('customer', '6281111111111');
        $this->ketua = $this->user('customer', '6282222222222');
        $this->admin = $this->user('admin', '6283333333333');
        $this->tim = Team::create(['name' => 'Umum', 'short_name' => 'umum']);
        $this->tim->chiefs()->attach($this->ketua);
        $this->barang = Barang::create(['stock_id' => 'B1', 'nama_barang' => 'Pulpen Biru', 'stock' => 10, 'satuan' => 'BUAH']);
    }

    private function user(string $role, ?string $phone): User
    {
        $username = Str::lower(Str::random(8));
        $user = User::forceCreate(['name' => "Nama $username", 'username' => $username, 'email' => "$username@example.com", 'password' => 'x', 'phone' => $phone]);
        $user->assignRole($role);

        return $user;
    }

    private function ajukan(): Order
    {
        $this->staf->cartItems()->create(['barang_id' => $this->barang->id, 'jumlah' => 3]);
        $kode = $this->actingAs($this->staf)->postJson(route('pengajuan.store'), [
            'team_id' => $this->tim->id, 'person_responsible_user_id' => $this->ketua->id, 'tanda_tangan' => self::TTD,
        ])->assertOk()->json('code');

        // Tautan diklik dari WhatsApp tanpa sesi login
        auth()->logout();
        $this->flushSession();

        return Order::where('code', $kode)->firstOrFail();
    }

    // Tautan persetujuan cepat yang benar-benar terkirim ke penerima ini lewat WhatsApp
    private function tautanUntuk(User $penerima, string $event): string
    {
        $pesan = WhatsappMessage::where('user_id', $penerima->id)->where('event', $event)->sole()->message;
        $this->assertSame(1, preg_match('#(https?://\S+/persetujuan-cepat/\S+)#', $pesan, $cocok), 'Pesan tidak berisi tautan persetujuan cepat.');

        return $cocok[1];
    }

    private function setujuiSebagaiKetua(Order $order): void
    {
        $tautan = $this->tautanUntuk($this->ketua, 'diajukan');
        $this->post($tautan, ['status' => 'disetujui', 'tanda_tangan' => self::TTD])->assertRedirect($tautan);
        $this->flushSession();
    }

    public function test_pesan_ke_ketua_berisi_tautan_dan_halaman_tampil_tanpa_login_tanpa_mengubah_apa_pun(): void
    {
        $order = $this->ajukan();

        $this->assertGuest();
        $this->get($this->tautanUntuk($this->ketua, 'diajukan'))->assertOk()
            ->assertSee($order->code)
            ->assertSee('Pulpen Biru')
            ->assertSee($this->staf->name)
            ->assertSee('Setujui')
            ->assertSee('Tanda Tangan')
            ->assertDontSee('href="' . route('login') . '"', false); // tanpa tombol Masuk

        $this->assertStringNotContainsString('tanpa login', WhatsappMessage::where('event', 'diajukan')->value('message'));
        $this->get(route('barang.katalog'))->assertSee('href="' . route('login') . '"', false); // halaman publik lain tetap punya tombol Masuk

        $this->assertSame('menunggu_ketua', $order->fresh()->status); // membuka tautan (pratinjau WhatsApp) tidak menyetujui
    }

    public function test_ketua_menyetujui_lewat_tautan_lalu_tiap_admin_menerima_tautannya_sendiri(): void
    {
        $adminLain = $this->user('admin', '6284444444444');
        $order = $this->ajukan();

        $this->setujuiSebagaiKetua($order);

        $order->refresh();
        $this->assertSame('menunggu_admin', $order->status);
        $this->assertSame($this->ketua->id, $order->approvalKetua->user_id);
        $this->assertNotNull($order->approvalKetua->signature_path);
        $this->assertNotSame($this->tautanUntuk($this->admin, 'disetujui_ketua'), $this->tautanUntuk($adminLain, 'disetujui_ketua'));
        $this->assertStringNotContainsString('persetujuan-cepat', WhatsappMessage::where('user_id', $this->staf->id)->where('event', 'disetujui_ketua')->value('message'));
    }

    public function test_ketua_wajib_tanda_tangan_untuk_menyetujui(): void
    {
        $order = $this->ajukan();
        $tautan = $this->tautanUntuk($this->ketua, 'diajukan');

        $this->from($tautan)->post($tautan, ['status' => 'disetujui', 'tanda_tangan' => ''])->assertSessionHasErrors('tanda_tangan');

        $this->assertSame('menunggu_ketua', $order->fresh()->status);
    }

    public function test_ketua_menolak_dengan_alasan_tanpa_tanda_tangan(): void
    {
        $order = $this->ajukan();
        $tautan = $this->tautanUntuk($this->ketua, 'diajukan');

        $this->post($tautan, ['status' => 'ditolak', 'alasan' => 'Tidak mendesak'])->assertRedirect($tautan)->assertSessionHas('success');

        $this->assertSame('ditolak', $order->fresh()->status);
        $this->assertSame('Tidak mendesak', $order->approvals()->value('catatan'));
    }

    public function test_halaman_memakai_dialog_setujui_dan_tolak_seperti_di_aplikasi(): void
    {
        $this->ajukan();

        $this->get($this->tautanUntuk($this->ketua, 'diajukan'))->assertOk()
            ->assertSee('Setujui Pengajuan Persediaan?')
            ->assertSee('Ya, Setujui')
            ->assertSee('Tanda Tangan Ketua Tim/Penanggung Jawab')
            ->assertSee('Tolak Pengajuan Persediaan')
            ->assertSee('Alasan Penolakan');
    }

    public function test_menolak_wajib_alasan_dan_dialog_tolak_terbuka_lagi(): void
    {
        $order = $this->ajukan();
        $tautan = $this->tautanUntuk($this->ketua, 'diajukan');

        $this->from($tautan)->post($tautan, ['status' => 'ditolak', 'alasan' => ''])
            ->assertRedirect($tautan)
            ->assertSessionHasErrors('alasan');

        // Kembali ke halaman: dialog Tolak langsung terbuka berisi pesan galat
        $this->followingRedirects()->from($tautan)->post($tautan, ['status' => 'ditolak', 'alasan' => ''])
            ->assertSee('Isi alasan penolakan terlebih dahulu.')
            ->assertSee("dialog: 'ditolak'", false);

        $this->assertSame('menunggu_ketua', $order->fresh()->status);
    }

    public function test_admin_menyetujui_lewat_tautan_memotong_stok_dan_memberi_nomor(): void
    {
        $order = $this->ajukan();
        $this->setujuiSebagaiKetua($order);
        $tautan = $this->tautanUntuk($this->admin, 'disetujui_ketua');

        $this->post($tautan, ['status' => 'disetujui'])->assertRedirect($tautan)->assertSessionHas('success');

        $order->refresh();
        $this->assertSame('disetujui', $order->status);
        $this->assertSame(7, $this->barang->fresh()->stock);
        $this->assertNotNull($order->nomor_urut);
        $this->assertSame($this->admin->id, $order->approvals()->where('level', 'admin')->value('user_id'));
        $this->assertGuest();
    }

    public function test_admin_kedua_tidak_bisa_menimpa_keputusan_admin_pertama(): void
    {
        $adminLain = $this->user('admin', '6284444444444');
        $adminLain->update(['name' => 'Admin Kedua']);
        $this->admin->update(['name' => 'Admin Pertama']);
        $order = $this->ajukan();
        $this->setujuiSebagaiKetua($order);
        $tautanPertama = $this->tautanUntuk($this->admin, 'disetujui_ketua');
        $tautanKedua = $this->tautanUntuk($adminLain, 'disetujui_ketua');

        // Admin kedua sudah membuka halaman (masih ada tombol), lalu admin pertama menyetujui lebih dulu
        $this->get($tautanKedua)->assertSee('Ya, Setujui');
        $this->post($tautanPertama, ['status' => 'disetujui'])->assertSessionHas('success');
        $this->flushSession();

        // Admin kedua mengirim dari halaman lama: ditolak, dan diberi tahu siapa yang sudah memutuskan
        $this->post($tautanKedua, ['status' => 'ditolak', 'alasan' => 'Coba menimpa'])
            ->assertRedirect($tautanKedua)
            ->assertSessionHas('error', fn ($pesan) => str_contains($pesan, 'sudah disetujui oleh admin Admin Pertama'));
        $this->flushSession();

        $order->refresh();
        $this->assertSame('disetujui', $order->status);
        $this->assertSame(1, $order->approvals()->where('level', 'admin')->count());
        $this->assertSame($this->admin->id, $order->approvals()->where('level', 'admin')->value('user_id'));
        $this->assertSame(7, $this->barang->fresh()->stock); // stok dipotong sekali saja
        $this->assertSame(1, WhatsappMessage::where('event', 'keputusan_admin')->count()); // staf dikabari sekali

        // Membuka ulang tautan: tidak ada tombol, tampil siapa yang memutuskan
        $this->get($tautanKedua)->assertOk()
            ->assertSee('sudah disetujui oleh admin Admin Pertama')
            ->assertDontSee('Ya, Setujui');
    }

    public function test_admin_kedua_tidak_bisa_menyetujui_yang_sudah_ditolak_admin_pertama(): void
    {
        $adminLain = $this->user('admin', '6284444444444');
        $order = $this->ajukan();
        $this->setujuiSebagaiKetua($order);

        $this->post($this->tautanUntuk($this->admin, 'disetujui_ketua'), ['status' => 'ditolak', 'alasan' => 'Stok untuk kegiatan lain']);
        $this->flushSession();
        $this->post($this->tautanUntuk($adminLain, 'disetujui_ketua'), ['status' => 'disetujui'])->assertSessionHas('error');

        $this->assertSame('ditolak', $order->fresh()->status);
        $this->assertSame(10, $this->barang->fresh()->stock); // stok tidak dipotong
    }

    public function test_tautan_mati_setelah_pengajuan_diproses(): void
    {
        $order = $this->ajukan();
        $tautan = $this->tautanUntuk($this->ketua, 'diajukan');
        $this->post($tautan, ['status' => 'ditolak', 'alasan' => 'Tidak mendesak'])->assertRedirect($tautan);
        $this->flushSession();

        $this->get($tautan)->assertOk()->assertSee('Tidak ada keputusan yang perlu Anda ambil')->assertDontSee('name="status"', false);
        $this->post($tautan, ['status' => 'disetujui', 'tanda_tangan' => self::TTD])->assertSessionHas('error');

        $this->assertSame('ditolak', $order->fresh()->status);
    }

    public function test_tautan_ketua_tidak_bisa_dipakai_di_tahap_admin(): void
    {
        $order = $this->ajukan();
        $this->setujuiSebagaiKetua($order);

        $this->post($this->tautanUntuk($this->ketua, 'diajukan'), ['status' => 'disetujui'])->assertSessionHas('error');

        $this->assertSame('menunggu_admin', $order->fresh()->status);
    }

    public function test_tautan_tanpa_tanda_tangan_atau_diubah_ditolak(): void
    {
        $order = $this->ajukan();
        $tautan = $this->tautanUntuk($this->ketua, 'diajukan');

        // Tanpa signature
        $this->get(route('persetujuan-cepat.show', ['order' => $order, 'user' => $this->ketua]))->assertForbidden();
        // Signature milik ketua, tapi pengguna diganti admin
        $this->post(str_replace($this->ketua->id, $this->admin->id, $tautan), ['status' => 'ditolak'])->assertForbidden();
        // Tautan bertanda tangan untuk orang yang bukan ketua tim pengajuan ini: tidak ada aksi
        $this->post(URL::signedRoute('persetujuan-cepat.store', ['order' => $order, 'user' => $this->staf]), ['status' => 'ditolak'])->assertSessionHas('error');

        $this->assertSame('menunggu_ketua', $order->fresh()->status);
    }
}
