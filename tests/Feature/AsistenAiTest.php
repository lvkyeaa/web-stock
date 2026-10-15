<?php

namespace Tests\Feature;

use App\Models\Barang;
use App\Models\Facility;
use App\Models\FacilityType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AsistenAiTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        $username = Str::lower(Str::random(8));
        $user = User::forceCreate(['name' => $username, 'username' => $username, 'email' => "$username@example.com", 'password' => 'x']);
        $user->assignRole($role);

        return $user;
    }

    // Potongan JSON seperti yang ditulis @js di halaman (tanda petik di-escape)
    private function js(string $json): string
    {
        return str_replace('"', '\\' . 'u0022', $json);
    }

    // Balasan palsu 9router; isi pesan AI boleh terbungkus ```json
    private function aiMembalas(array $hasil): void
    {
        config(['services.ai.url' => 'http://9router.test/v1']);
        Http::fake(['9router.test/*' => Http::response([
            'choices' => [['message' => ['content' => "```json\n" . json_encode($hasil) . "\n```"]]],
        ])]);
    }

    public function test_permintaan_di_luar_konteks_aplikasi_ditolak(): void
    {
        $this->aiMembalas(['intent' => 'tolak']);

        $this->actingAs($this->user('customer'))
            ->postJson(route('asisten-ai.process'), ['message' => 'Siapa presiden pertama Indonesia?', 'context' => 'dashboard'])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Maaf, Asisten AI hanya dapat membantu peminjaman fasilitas dan pengajuan persediaan.');
    }

    public function test_permintaan_persediaan_di_menu_peminjaman_ditolak(): void
    {
        $this->aiMembalas(['intent' => 'persediaan', 'persediaan' => ['items' => []]]);

        $this->actingAs($this->user('customer'))
            ->postJson(route('asisten-ai.process'), ['message' => 'Minta 2 rim kertas', 'context' => 'peminjaman'])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Pengajuan persediaan tidak dapat dibuat di sini. Gunakan Asisten AI di halaman Katalog Persediaan atau Dashboard.');

        Http::assertSent(fn($request) => $request->url() === 'http://9router.test/v1/chat/completions'
            && str_contains($request['messages'][1]['content'], '<permintaan>'));
    }

    public function test_balasan_stream_sse_dari_9router_dapat_dibaca(): void
    {
        config(['services.ai.url' => 'http://9router.test/v1']);
        $potongan = ['```json', "\n{\"intent\":", '"tolak"}', "\n```"];
        Http::fake(['9router.test/*' => Http::response(collect($potongan)
            ->map(fn($isi) => 'data: ' . json_encode(['choices' => [['delta' => ['content' => $isi]]]]))
            ->push('data: [DONE]')->implode("\n"))]);

        $this->actingAs($this->user('customer'))
            ->postJson(route('asisten-ai.process'), ['message' => 'Ceritakan lelucon', 'context' => 'dashboard'])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Maaf, Asisten AI hanya dapat membantu peminjaman fasilitas dan pengajuan persediaan.');
    }

    public function test_ai_gagal_mengembalikan_pesan(): void
    {
        config(['services.ai.url' => 'http://9router.test/v1']);
        Http::fake(['9router.test/*' => Http::response(['choices' => [['message' => ['content' => 'bukan json']]]])]);

        $this->actingAs($this->user('customer'))
            ->postJson(route('asisten-ai.process'), ['message' => 'Pinjam mobil', 'context' => 'dashboard'])
            ->assertStatus(503);
    }

    public function test_peminjaman_mobil_tanpa_model_mengisi_jenis_saja(): void
    {
        $mobil = FacilityType::create(['code' => 'car', 'name' => 'Mobil Dinas', 'icon' => '🚗', 'color' => '#f97316']);
        Facility::create(['facility_type_id' => $mobil->id, 'name' => 'Avanza L 1234 AB']);
        $this->aiMembalas(['intent' => 'peminjaman', 'peminjaman' => [
            'type' => 'car', 'facility_id' => null,
            'waktu_mulai' => '2026-10-19T08:00', 'waktu_selesai' => '2026-10-19T16:00', 'keperluan' => 'Kunjungan ke Malang',
        ]]);

        $this->actingAs($this->user('customer'))
            ->postJson(route('asisten-ai.process'), ['message' => 'Pinjam mobil dinas Senin depan ke Malang', 'context' => 'peminjaman'])
            ->assertOk()
            ->assertJsonPath('redirect', route('peminjaman.create'));

        $this->get(route('peminjaman.create'))->assertOk()
            ->assertSee('Formulir diisi otomatis oleh Asisten AI')
            ->assertSee($this->js('"jenis":"car"'), false)
            ->assertSee($this->js('"oldItem":""'), false)
            ->assertSee('2026-10-19T08:00')
            ->assertSee('Kunjungan ke Malang');
    }

    public function test_peminjaman_ruang_rapat_mengisi_jumlah_peserta_dan_kebutuhan(): void
    {
        FacilityType::create(['code' => 'room', 'name' => 'Ruang Rapat', 'icon' => '🏢', 'color' => '#0284c7']);
        $this->aiMembalas(['intent' => 'peminjaman', 'peminjaman' => [
            'type' => 'room', 'facility_id' => null, 'waktu_mulai' => null, 'waktu_selesai' => null, 'keperluan' => 'Rapat tim',
            'jumlah_peserta' => 20, 'butuh_sound_system' => false, 'butuh_konsumsi' => true,
        ]]);

        $this->actingAs($this->user('customer'))
            ->postJson(route('asisten-ai.process'), ['message' => 'Ruang rapat untuk 20 orang, perlu snack', 'context' => 'peminjaman'])
            ->assertOk();

        $this->assertSame(['jenis' => 'room', 'keperluan' => 'Rapat tim', 'jumlah_peserta' => 20, 'butuh_konsumsi' => '1'], session()->getOldInput());
    }

    public static function balasanJumlahPeserta(): array
    {
        return [
            'angka'            => [20, 20],
            'teks angka'       => ['20', 20],
            'nol'              => ['0', null],
            'bukan angka'      => ['dua puluh', null],
            'melebihi kolom'   => ['70000', null],
        ];
    }

    // Jumlah peserta dari AI boleh angka atau teks angka; selain itu / di luar batas kolom diabaikan
    #[DataProvider('balasanJumlahPeserta')]
    public function test_jumlah_peserta_dari_ai(int|string $balasan, ?int $harapan): void
    {
        FacilityType::create(['code' => 'room', 'name' => 'Ruang Rapat', 'icon' => '🏢', 'color' => '#0284c7']);
        $this->aiMembalas(['intent' => 'peminjaman', 'peminjaman' => [
            'type' => 'room', 'facility_id' => null, 'waktu_mulai' => null, 'waktu_selesai' => null, 'keperluan' => null,
            'jumlah_peserta' => $balasan, 'butuh_sound_system' => false, 'butuh_konsumsi' => false,
        ]]);

        $this->actingAs($this->user('customer'))
            ->postJson(route('asisten-ai.process'), ['message' => 'Ruang rapat untuk peserta', 'context' => 'peminjaman'])
            ->assertOk();

        $this->assertSame($harapan, session()->getOldInput('jumlah_peserta'));
    }

    public function test_detail_ruang_rapat_diabaikan_untuk_jenis_lain(): void
    {
        FacilityType::create(['code' => 'car', 'name' => 'Mobil Dinas', 'icon' => '🚗', 'color' => '#f97316']);
        $this->aiMembalas(['intent' => 'peminjaman', 'peminjaman' => [
            'type' => 'car', 'facility_id' => null, 'waktu_mulai' => null, 'waktu_selesai' => null, 'keperluan' => null,
            'jumlah_peserta' => 4, 'butuh_sound_system' => true, 'butuh_konsumsi' => true,
        ]]);

        $this->actingAs($this->user('customer'))
            ->postJson(route('asisten-ai.process'), ['message' => 'Pinjam mobil untuk 4 orang', 'context' => 'peminjaman'])
            ->assertOk();

        $this->assertSame(['jenis' => 'car'], session()->getOldInput());
    }

    public function test_peminjaman_fasilitas_beda_jenis_atau_waktu_tidak_valid_diabaikan(): void
    {
        $mobil = FacilityType::create(['code' => 'car', 'name' => 'Mobil Dinas', 'icon' => '🚗', 'color' => '#f97316']);
        $ruangRapat = FacilityType::create(['code' => 'room', 'name' => 'Ruang Rapat', 'icon' => '🏢', 'color' => '#0284c7']);
        Facility::create(['facility_type_id' => $mobil->id, 'name' => 'Avanza']);
        $ruang = Facility::create(['facility_type_id' => $ruangRapat->id, 'name' => 'Ruang A']);
        $this->aiMembalas(['intent' => 'peminjaman', 'peminjaman' => [
            'type' => 'car', 'facility_id' => $ruang->id, 'waktu_mulai' => 'besok jam 9', 'waktu_selesai' => null, 'keperluan' => null,
        ]]);

        $this->actingAs($this->user('admin'))
            ->postJson(route('asisten-ai.process'), ['message' => 'Pinjam mobil besok', 'context' => 'dashboard'])
            ->assertOk();

        $this->assertSame(['jenis' => 'car'], session()->getOldInput());
    }

    public function test_persediaan_membuka_halaman_rekomendasi_berisi_barang_tersedia_saja(): void
    {
        $biru = Barang::create(['nama_barang' => 'Ballpoint Biru', 'stock' => 10, 'satuan' => 'buah']);
        $hitam = Barang::create(['nama_barang' => 'Ballpoint Hitam', 'stock' => 0, 'satuan' => 'buah']);
        $kertas = Barang::create(['nama_barang' => 'Kertas HVS 80 Gram A4', 'stock' => 5, 'satuan' => 'rim']);
        $this->aiMembalas(['intent' => 'persediaan', 'persediaan' => ['permintaan' => [
            ['teks' => '3 ballpoint', 'jumlah' => 3, 'pilihan' => [$biru->id, $hitam->id, 'karangan'], 'tepat' => null],
            ['teks' => 'kertas HVS 80 gram A4', 'jumlah' => null, 'pilihan' => [$kertas->id], 'tepat' => $kertas->id],
            ['teks' => 'laptop', 'jumlah' => 1, 'pilihan' => [], 'tepat' => null],
        ]]]);

        $this->actingAs($this->user('customer'))
            ->postJson(route('asisten-ai.process'), ['message' => '3 ballpoint, kertas HVS 80 gram A4, laptop', 'context' => 'dashboard'])
            ->assertOk()
            ->assertJsonPath('redirect', route('asisten-ai.supplies'));

        // Stok habis & id karangan AI tidak ditampilkan; tanpa angka → 1; yang tepat sudah dicentang
        $this->get(route('asisten-ai.supplies'))
            ->assertOk()
            ->assertSee('3 ballpoint, kertas HVS 80 gram A4, laptop')
            ->assertSee('Ballpoint Biru')
            ->assertDontSee('Ballpoint Hitam')
            ->assertSee('× 3 buah')
            ->assertSee('× 1 rim')
            ->assertSee('value="' . $kertas->id . '" checked', false)
            ->assertDontSee('value="' . $biru->id . '" checked', false)
            ->assertSee('laptop')
            ->assertSee('Masukkan ke Keranjang');
    }

    public function test_barang_dipilih_mengganti_keranjang_dengan_jumlah_dibatasi_stok(): void
    {
        $biru = Barang::create(['nama_barang' => 'Ballpoint Biru', 'stock' => 2, 'satuan' => 'buah']);
        $lain = Barang::create(['nama_barang' => 'Laptop', 'stock' => 10, 'satuan' => 'unit']);
        $map = Barang::create(['nama_barang' => 'Map Plastik', 'stock' => 10, 'satuan' => 'buah']);
        $user = $this->user('customer');
        $user->cartItems()->create(['barang_id' => $map->id, 'jumlah' => 3]);
        $this->aiMembalas(['intent' => 'persediaan', 'persediaan' => ['permintaan' => [
            ['teks' => '3 ballpoint', 'jumlah' => 3, 'pilihan' => [$biru->id], 'tepat' => null],
        ]]]);

        $this->actingAs($user)
            ->postJson(route('asisten-ai.process'), ['message' => '3 ballpoint', 'context' => 'persediaan'])
            ->assertOk();

        // Keranjang lama belum diubah; halaman memberi peringatan penggantian & batas stok
        $this->assertSame([$map->id => 3], $user->cartItems()->pluck('jumlah', 'barang_id')->all());
        $this->get(route('asisten-ai.supplies'))
            ->assertSee('× 2 buah')
            ->assertSee('maks')
            ->assertSee('Keranjang Anda berisi 1 persediaan')
            ->assertSee('Ganti Isi Keranjang');

        // Barang di luar pilihan permintaan itu diabaikan
        $this->post(route('asisten-ai.apply'), ['selected' => [0 => [$biru->id, $lain->id]]])
            ->assertRedirect(route('keranjang.index'))
            ->assertSessionHas('success');

        $this->assertSame([$biru->id => 2], $user->cartItems()->pluck('jumlah', 'barang_id')->all());

        // Rekomendasi hanya bisa dipakai sekali
        $this->get(route('asisten-ai.supplies'))->assertRedirect(route('barang.katalog'));
    }

    public function test_tanpa_pilihan_kembali_ke_halaman_rekomendasi(): void
    {
        $biru = Barang::create(['nama_barang' => 'Ballpoint Biru', 'stock' => 10, 'satuan' => 'buah']);
        $map = Barang::create(['nama_barang' => 'Map Plastik', 'stock' => 10, 'satuan' => 'buah']);
        $user = $this->user('customer');
        $user->cartItems()->create(['barang_id' => $map->id, 'jumlah' => 3]);
        $this->aiMembalas(['intent' => 'persediaan', 'persediaan' => ['permintaan' => [
            ['teks' => 'ballpoint', 'jumlah' => null, 'pilihan' => [$biru->id], 'tepat' => null],
        ]]]);

        $this->actingAs($user)->postJson(route('asisten-ai.process'), ['message' => 'ballpoint', 'context' => 'persediaan'])->assertOk();

        $this->post(route('asisten-ai.apply'))
            ->assertRedirect(route('asisten-ai.supplies'))
            ->assertSessionHas('error', 'Pilih minimal satu persediaan.');

        $this->assertSame([$map->id => 3], $user->cartItems()->pluck('jumlah', 'barang_id')->all());
        $this->get(route('asisten-ai.supplies'))->assertOk();
    }

    public function test_persediaan_tanpa_barang_tersedia_ditolak(): void
    {
        $habis = Barang::create(['nama_barang' => 'Laptop', 'stock' => 0, 'satuan' => 'unit']);
        $this->aiMembalas(['intent' => 'persediaan', 'persediaan' => ['permintaan' => [
            ['teks' => 'laptop', 'jumlah' => 1, 'pilihan' => [$habis->id], 'tepat' => $habis->id],
        ]]]);

        $this->actingAs($this->user('customer'))
            ->postJson(route('asisten-ai.process'), ['message' => 'Minta laptop', 'context' => 'persediaan'])
            ->assertUnprocessable()
            ->assertJsonPath('notes', ['laptop (tidak ditemukan)']);

        $this->get(route('asisten-ai.supplies'))->assertRedirect(route('barang.katalog'));
    }
}
