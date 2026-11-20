<?php

namespace Tests\Feature;

use App\Models\Barang;
use App\Models\Order;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

// Lonceng notifikasi dari tabel notifications: kabar pengajuan persediaan (sama penerimanya dengan WhatsApp) + peminjaman
class NotifikasiTest extends TestCase
{
    use RefreshDatabase;

    private const TTD = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

    private User $staf;
    private User $ketua;
    private User $admin;
    private Team $tim;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Http::preventStrayRequests();
        Http::fake(['whatsapp.test/*' => Http::response(['status' => 'ok'], 201)]);

        $this->staf = $this->user('customer', 'Staf Satu', '6281111111111');
        $this->ketua = $this->user('customer', 'Ketua Satu', '6282222222222');
        $this->admin = $this->user('admin', 'Admin Satu', null); // tanpa nomor: WhatsApp dilewati, lonceng tetap
        $this->tim = Team::create(['name' => 'Umum', 'short_name' => 'umum']);
        $this->tim->chiefs()->attach($this->ketua);
    }

    private function user(string $role, string $nama, ?string $phone): User
    {
        $username = Str::lower(Str::random(8));
        $user = User::forceCreate(['name' => $nama, 'username' => $username, 'email' => "$username@example.com", 'password' => 'x', 'phone' => $phone]);
        $user->assignRole($role);

        return $user;
    }

    private function ajukan(): Order
    {
        $barang = Barang::create(['stock_id' => 'B1', 'nama_barang' => 'Pulpen', 'stock' => 10, 'satuan' => 'BUAH']);
        $this->staf->cartItems()->create(['barang_id' => $barang->id, 'jumlah' => 2]);
        $kode = $this->actingAs($this->staf)->postJson(route('pengajuan.store'), [
            'team_id' => $this->tim->id, 'person_responsible_user_id' => $this->ketua->id, 'tanda_tangan' => self::TTD,
        ])->assertOk()->json('code');
        $this->flushSession();

        return Order::where('code', $kode)->firstOrFail();
    }

    private function pesanUntuk(User $user): array
    {
        return $user->notifications()->get()->pluck('data.pesan')->all();
    }

    public function test_pengajuan_baru_tercatat_di_lonceng_ketua_tim_dengan_tautan_aplikasi(): void
    {
        $order = $this->ajukan();

        $notifikasi = $this->ketua->notifications()->sole();
        $this->assertNull($notifikasi->read_at);
        $this->assertSame($order->id, $notifikasi->data['order_id']);
        $this->assertSame("Pengajuan {$order->code} dari Staf Satu (1 barang) menunggu persetujuan Anda.", $notifikasi->data['pesan']);
        $this->assertSame(route('pengajuan.ketua'), $notifikasi->data['url']); // bukan tautan persetujuan cepat
        $this->assertSame([], $this->pesanUntuk($this->staf));
    }

    public function test_persetujuan_dan_penolakan_tercatat_untuk_penerima_yang_sama_dengan_whatsapp(): void
    {
        $order = $this->ajukan();
        $this->actingAs($this->ketua)->patchJson(route('pengajuan.keputusan-ketua', $order), ['status' => 'disetujui', 'tanda_tangan' => self::TTD])->assertOk();

        // Admin tanpa nomor HP tetap mendapat notifikasi lonceng
        $this->assertSame(["Pengajuan {$order->code} dari Staf Satu telah disetujui ketua tim Ketua Satu dan menunggu persetujuan Anda."], $this->pesanUntuk($this->admin));
        $this->assertSame(route('pengajuan.index', ['status' => 'menunggu_admin']), $this->admin->notifications()->sole()->data['url']);
        $this->assertContains("Pengajuan {$order->code} Anda telah disetujui ketua tim Ketua Satu dan menunggu persetujuan admin.", $this->pesanUntuk($this->staf));

        $this->actingAs($this->admin)->patchJson(route('pengajuan.update-status', $order), ['status' => 'ditolak', 'alasan' => 'Stok untuk kegiatan lain'])->assertOk();

        $this->assertContains("Pengajuan {$order->code} Anda ditolak oleh admin.\nAlasan: Stok untuk kegiatan lain", $this->pesanUntuk($this->staf));
        $this->assertSame(route('pengajuan.index'), $this->staf->notifications()->latest()->first()->data['url']);
    }

    public function test_penolakan_ketua_tercatat_di_lonceng_staf(): void
    {
        $order = $this->ajukan();
        $this->actingAs($this->ketua)->patchJson(route('pengajuan.keputusan-ketua', $order), ['status' => 'ditolak', 'alasan' => 'Tidak mendesak'])->assertOk();

        $this->assertSame(["Pengajuan {$order->code} Anda ditolak oleh ketua tim Ketua Satu.\nAlasan: Tidak mendesak"], $this->pesanUntuk($this->staf));
        $this->assertSame([], $this->pesanUntuk($this->admin)); // admin tidak perlu bertindak
    }

    public function test_lonceng_menampilkan_jumlah_belum_dibaca_pengajuan_dan_peminjaman(): void
    {
        $order = $this->ajukan();
        // Notifikasi peminjaman lama (format pesan + url) tampil di lonceng yang sama
        $this->ketua->notifications()->create([
            'id' => (string) Str::uuid(), 'type' => 'App\Notifications\StatusPeminjamanDiperbarui',
            'data' => ['peminjaman_id' => 1, 'pesan' => 'Pengajuan peminjaman Mobil Avanza Anda telah DISETUJUI', 'url' => route('peminjaman.index')],
        ]);

        $this->actingAs($this->ketua)->get(route('profil.index'))->assertOk()
            ->assertSee('Notifikasi (2 belum dibaca)')
            ->assertSee("Pengajuan {$order->code} dari Staf Satu")
            ->assertSee('Pengajuan peminjaman Mobil Avanza Anda telah DISETUJUI')
            ->assertSee('Tandai semua dibaca');

        $this->actingAs($this->admin)->get(route('profil.index'))->assertOk()->assertSee('Belum ada notifikasi');
    }

    public function test_membuka_notifikasi_menandai_dibaca_lalu_ke_tautannya(): void
    {
        $this->ajukan();
        $notifikasi = $this->ketua->notifications()->sole();

        $this->actingAs($this->ketua)->get(route('notifikasi.buka', $notifikasi->id))->assertRedirect(route('pengajuan.ketua'));

        $this->assertNotNull($notifikasi->fresh()->read_at);
        $this->actingAs($this->ketua)->get(route('profil.index'))->assertDontSee('belum dibaca)');
    }

    public function test_tidak_bisa_membuka_notifikasi_milik_orang_lain(): void
    {
        $this->ajukan();
        $notifikasi = $this->ketua->notifications()->sole();

        $this->actingAs($this->staf)->get(route('notifikasi.buka', $notifikasi->id))->assertNotFound();

        $this->assertNull($notifikasi->fresh()->read_at);
    }

    public function test_tandai_semua_dibaca_hanya_milik_sendiri(): void
    {
        $order = $this->ajukan();
        $this->actingAs($this->ketua)->patchJson(route('pengajuan.keputusan-ketua', $order), ['status' => 'disetujui', 'tanda_tangan' => self::TTD])->assertOk();

        $this->actingAs($this->staf)->from(route('profil.index'))->post(route('notifikasi.baca-semua'))->assertRedirect(route('profil.index'));

        $this->assertSame(0, $this->staf->unreadNotifications()->count());
        $this->assertSame(1, $this->admin->unreadNotifications()->count());
        $this->assertSame(1, $this->ketua->unreadNotifications()->count());
    }

    public function test_tamu_tidak_bisa_mengakses_notifikasi(): void
    {
        $this->get(route('notifikasi.buka', Str::uuid()))->assertRedirect(route('login'));
        $this->post(route('notifikasi.baca-semua'))->assertRedirect(route('login'));
    }
}
