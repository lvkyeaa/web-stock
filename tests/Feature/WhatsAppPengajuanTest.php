<?php

namespace Tests\Feature;

use App\Jobs\KirimWhatsApp;
use App\Models\Barang;
use App\Models\Order;
use App\Models\Team;
use App\Models\User;
use App\Models\WhatsappMessage;
use App\Services\WhatsApp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

// Pesan WhatsApp pengajuan: siapa menerima pada tiap kejadian, dan log terkirim / gagal / dilewati
class WhatsAppPengajuanTest extends TestCase
{
    use RefreshDatabase;

    private const TTD = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

    private User $staf;
    private User $ketua;
    private User $admin;
    private Team $tim;
    private array $balasanApi = [['status' => 'ok'], 201]; // [body, status] balasan API palsu

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Http::preventStrayRequests();
        Http::fake(['whatsapp.test/*' => fn () => Http::response($this->balasanApi[0], $this->balasanApi[1])]);

        $this->staf = $this->user('customer', '6281111111111');
        $this->ketua = $this->user('customer', '6282222222222');
        $this->admin = $this->user('admin', '6283333333333');
        $this->tim = Team::create(['name' => 'Umum', 'short_name' => 'umum']);
        $this->tim->chiefs()->attach($this->ketua);
    }

    private function user(string $role, ?string $phone): User
    {
        $username = Str::lower(Str::random(8));
        $user = User::forceCreate(['name' => $username, 'username' => $username, 'email' => "$username@example.com", 'password' => 'x', 'phone' => $phone]);
        $user->assignRole($role);

        return $user;
    }

    private function isiKeranjang(): void
    {
        $barang = Barang::create(['stock_id' => 'B1', 'nama_barang' => 'Pulpen', 'stock' => 10, 'satuan' => 'BUAH']);
        $this->staf->cartItems()->create(['barang_id' => $barang->id, 'jumlah' => 2]);
    }

    private function kirim(array $data = []): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->staf)->postJson(route('pengajuan.store'), $data + [
            'team_id' => $this->tim->id, 'person_responsible_user_id' => $this->ketua->id, 'tanda_tangan' => self::TTD,
        ]);
    }

    private function ajukan(): Order
    {
        $this->isiKeranjang();
        $kode = $this->kirim()->assertOk()->json('code');
        $this->flushSession();

        return Order::where('code', $kode)->firstOrFail();
    }

    private function keputusanKetua(Order $order, string $status, ?string $alasan = null): void
    {
        $this->actingAs($this->ketua)->patchJson(route('pengajuan.keputusan-ketua', $order), [
            'status' => $status, 'alasan' => $alasan, 'tanda_tangan' => self::TTD,
        ])->assertOk();
    }

    // Penerima (nomor HP) tiap pesan untuk satu kejadian
    private function penerima(string $event): array
    {
        return WhatsappMessage::where('event', $event)->orderBy('phone')->pluck('phone')->all();
    }

    public function test_pengajuan_baru_dikirim_ke_ketua_tim_dengan_format_api_majapahit(): void
    {
        $order = $this->ajukan();

        $log = WhatsappMessage::sole();
        $this->assertSame(['diajukan', '6282222222222', 'terkirim', 1, 201], [$log->event, $log->phone, $log->status, $log->attempts, $log->response_code]);
        $this->assertNotNull($log->sent_at);
        $this->assertStringContainsString($order->code, $log->message);
        $this->assertStringStartsWith("*[Wirabhumi - Aplikasi Layanan Fasilitas dan Persediaan]* 📝\n\nPengajuan ", $log->message);
        $this->assertStringContainsString("menunggu persetujuan Anda.\n\nSetujui atau tolak di sini:\nhttp", $log->message);

        Http::assertSent(fn (Request $request) => $request->url() === 'https://whatsapp.test/push-pesan/'
            && $request->hasHeader('EXTERNAL-API-KEY', 'kunci-uji')
            && $request->hasHeader('Referer', 'majapahit')
            && $request['id_whatsapp'] === '6282222222222@c.us'
            && $request['pesan'] === $log->message);
    }

    public function test_ketua_menyetujui_dikirim_ke_admin_dan_staf(): void
    {
        $adminTanpaHp = $this->user('admin', null);
        $order = $this->ajukan();

        $this->keputusanKetua($order, 'disetujui');

        $this->assertSame([null, '6281111111111', '6283333333333'], $this->penerima('disetujui_ketua'));
        $this->assertSame('dilewati', WhatsappMessage::where('user_id', $adminTanpaHp->id)->value('status'));
        $this->assertSame(0, WhatsappMessage::where('user_id', $adminTanpaHp->id)->value('attempts'));
        Http::assertSentCount(3); // diajukan + 2 penerima bernomor
    }

    public function test_ketua_menolak_hanya_dikirim_ke_staf_dengan_alasan(): void
    {
        $order = $this->ajukan();

        $this->keputusanKetua($order, 'ditolak', 'Stok cukup di ruangan');

        $this->assertSame(['6281111111111'], $this->penerima('ditolak_ketua'));
        $pesan = WhatsappMessage::where('event', 'ditolak_ketua')->value('message');
        $this->assertStringContainsString('Alasan: Stok cukup di ruangan', $pesan);
        $this->assertStringStartsWith('*[Wirabhumi - Aplikasi Layanan Fasilitas dan Persediaan]* ❌', $pesan);
    }

    public function test_keputusan_admin_dikirim_ke_staf(): void
    {
        $order = $this->ajukan();
        $this->keputusanKetua($order, 'disetujui');

        $this->actingAs($this->admin)->patchJson(route('pengajuan.update-status', $order), ['status' => 'disetujui'])->assertOk();

        $this->assertSame(['6281111111111'], $this->penerima('keputusan_admin'));
        $pesan = WhatsappMessage::where('event', 'keputusan_admin')->value('message');
        $this->assertStringContainsString('disetujui admin', $pesan);
        $this->assertStringStartsWith('*[Wirabhumi - Aplikasi Layanan Fasilitas dan Persediaan]* ✅', $pesan);
    }

    // WhatsApp hanya menjadikan URL bisa diklik jika berdiri sendiri: tiap tautan absolut (http/https) di barisnya sendiri
    public function test_semua_tautan_di_pesan_berada_di_baris_sendiri_agar_bisa_diklik(): void
    {
        $order = $this->ajukan();
        $this->keputusanKetua($order, 'disetujui');
        $this->actingAs($this->admin)->patchJson(route('pengajuan.update-status', $order), ['status' => 'ditolak', 'alasan' => 'Stok habis'])->assertOk();

        $pesan = WhatsappMessage::pluck('message');
        $this->assertCount(4, $pesan); // ketua, admin, staf (disetujui ketua), staf (keputusan admin)

        foreach ($pesan as $isi) {
            $barisTautan = collect(explode("\n", $isi))->filter(fn ($baris) => str_contains($baris, '://'));
            $this->assertCount(1, $barisTautan, $isi);
            $this->assertMatchesRegularExpression('#^https?://\S+$#', $barisTautan->first(), $isi);
        }
    }

    public function test_penerima_tanpa_nomor_dilewati_tanpa_memanggil_api(): void
    {
        $this->ketua->update(['phone' => null]);

        $this->ajukan();

        $this->assertSame('dilewati', WhatsappMessage::sole()->status);
        Http::assertNothingSent();
    }

    public function test_api_gagal_dicatat_lalu_ditandai_gagal_setelah_percobaan_terakhir(): void
    {
        $this->balasanApi = [['error' => 'nomor tidak terdaftar'], 400];
        $log = WhatsappMessage::create([
            'order_id' => $this->ajukanTanpaKirim()->id, 'user_id' => $this->staf->id, 'phone' => '6281111111111',
            'event' => 'keputusan_admin', 'message' => 'Halo',
        ]);
        $job = new KirimWhatsApp($log);

        try {
            $job->handle(app(WhatsApp::class));
            $this->fail('Job harus melempar exception agar queue mencoba ulang.');
        } catch (RequestException) {
        }

        $log->refresh();
        $this->assertSame(['menunggu', 1, 400], [$log->status, $log->attempts, $log->response_code]);
        $this->assertStringContainsString('nomor tidak terdaftar', $log->error);

        $job->failed(null); // dipanggil queue setelah percobaan ke-3
        $this->assertSame('gagal', $log->fresh()->status);
    }

    // ─── Nomor WhatsApp di halaman keranjang ───

    public function test_kolom_nomor_whatsapp_hanya_tampil_jika_pemohon_belum_punya_nomor(): void
    {
        $this->isiKeranjang();
        $this->actingAs($this->staf)->get(route('keranjang.index'))->assertOk()->assertDontSee('id="phone"', false);

        $this->staf->update(['phone' => null]);
        $this->actingAs($this->staf)->get(route('keranjang.index'))->assertOk()
            ->assertSee('id="phone"', false)
            ->assertSee('Anda belum mengisi nomor WhatsApp');
    }

    public function test_nomor_dari_keranjang_disimpan_ke_profil_dan_dipakai_untuk_kabar_berikutnya(): void
    {
        $this->staf->update(['phone' => null]);
        $this->isiKeranjang();

        $kode = $this->kirim(['phone' => '6289999999999'])->assertOk()->json('code');
        $this->flushSession();
        $this->keputusanKetua(Order::where('code', $kode)->firstOrFail(), 'ditolak');

        $this->assertSame('6289999999999', $this->staf->fresh()->phone);
        $this->assertSame(['6289999999999'], $this->penerima('ditolak_ketua'));
    }

    public function test_nomor_tidak_valid_di_keranjang_ditolak_tanpa_membuat_pengajuan(): void
    {
        $this->staf->update(['phone' => null]);
        $this->isiKeranjang();

        $this->kirim(['phone' => '081234567890'])->assertJsonValidationErrors('phone');

        $this->assertSame(0, Order::count());
        $this->assertNull($this->staf->fresh()->phone);
    }

    public function test_pemohon_tanpa_nomor_wajib_mengisi_nomor_di_keranjang(): void
    {
        $this->staf->update(['phone' => null]);
        $this->isiKeranjang();

        $this->kirim(['phone' => ''])->assertJsonValidationErrors('phone');

        $this->assertSame(0, Order::count());
    }

    public function test_pemohon_yang_sudah_punya_nomor_tidak_perlu_mengisi_lagi(): void
    {
        $this->isiKeranjang();

        $this->kirim()->assertOk();
    }

    public function test_nomor_yang_sudah_ada_tidak_diganti_dari_keranjang(): void
    {
        $this->isiKeranjang();

        $this->kirim(['phone' => '6289999999999'])->assertOk();

        $this->assertSame('6281111111111', $this->staf->fresh()->phone);
    }

    // Pengajuan langsung di database, tanpa lewat checkout (tidak memicu pesan)
    private function ajukanTanpaKirim(): Order
    {
        return $this->staf->orders()->create([
            'code' => Order::buatKode(), 'status' => 'menunggu_admin', 'team_id' => $this->tim->id, 'person_responsible_user_id' => $this->ketua->id,
        ]);
    }
}
