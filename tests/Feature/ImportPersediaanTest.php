<?php

namespace Tests\Feature;

use App\Jobs\ImportPersediaan;
use App\Models\Barang;
use App\Models\ImportStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class ImportPersediaanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function user(string $role): User
    {
        $username = Str::lower(Str::random(8));
        $user = User::forceCreate(['name' => $username, 'username' => $username, 'email' => "$username@example.com", 'password' => 'x']);
        $user->assignRole($role);

        return $user;
    }

    private function barang(string $nama, int $stock): Barang
    {
        return Barang::create(['nama_barang' => $nama, 'stock' => $stock, 'satuan' => 'BUAH']);
    }

    // File CSV sudah tersimpan + status 'start', seperti setelah diunggah
    private function importDari(string $csv): ImportStatus
    {
        Storage::disk('local')->put('imports/uji.csv', $csv);

        return ImportStatus::create(['nama_file' => 'persediaan.csv', 'path' => 'imports/uji.csv', 'status' => 'start']);
    }

    private function jalankan(ImportStatus $import): ImportStatus
    {
        (new ImportPersediaan($import))->handle();

        return $import->fresh();
    }

    // ─── Unggah ───

    public function test_unggah_menyimpan_file_mencatat_status_dan_mengantrekan_job(): void
    {
        Queue::fake();
        $admin = $this->user('admin');

        $this->actingAs($admin)
            ->postJson(route('barang.import'), ['file_excel' => UploadedFile::fake()->createWithContent('persediaan.csv', "nama_barang,stock,satuan\nStapler,5,BUAH\n")])
            ->assertStatus(202)
            ->assertJson(['message' => "File 'persediaan.csv' diterima dan sedang diproses. Cek statusnya di Riwayat Impor."]);

        $import = ImportStatus::sole();
        $this->assertSame(['start', 'persediaan.csv', $admin->id], [$import->status, $import->nama_file, $import->user_id]);
        $this->assertStringEndsWith('.csv', $import->path);
        Storage::disk('local')->assertExists($import->path);
        Queue::assertPushed(ImportPersediaan::class, fn ($job) => $job->importStatus->is($import));
        $this->assertSame(0, Barang::count(), 'belum diproses sampai job berjalan');
    }

    public function test_unggah_menolak_file_bukan_excel(): void
    {
        Queue::fake();

        $this->actingAs($this->user('admin'))
            ->postJson(route('barang.import'), ['file_excel' => UploadedFile::fake()->createWithContent('catatan.txt', 'halo')])
            ->assertJsonValidationErrors('file_excel');

        $this->assertSame(0, ImportStatus::count());
        Queue::assertNothingPushed();
    }

    public function test_unggah_sampai_selesai_lewat_queue(): void
    {
        config(['queue.default' => 'sync']); // job langsung dijalankan
        $this->barang('Stapler', 2);

        $this->actingAs($this->user('admin'))
            ->postJson(route('barang.import'), ['file_excel' => UploadedFile::fake()->createWithContent('persediaan.csv', "nama_barang,stock,satuan\nStapler,5,BUAH\n")])
            ->assertStatus(202);

        $this->assertSame('success', ImportStatus::sole()->status);
        $this->assertSame(7, Barang::sole()->stock);
    }

    // ─── Proses job ───

    public function test_nama_sudah_ada_stok_ditambah_nama_baru_dibuat(): void
    {
        $lama = $this->barang('Stapler', 2);

        $import = $this->jalankan($this->importDari(
            "nama_barang,stock,satuan\n"
            . "  stapler ,5,PAK\n"       // nama sama (beda huruf & spasi): stok ditambah, satuan lama tetap
            . "Amplop Coklat,10,LEMBAR\n" // baru
            . "Lakban,3,\n"               // baru, satuan kosong → Pcs
            . "amplop coklat,4,LEMBAR\n"  // nama sama dengan baris di atas dalam file yang sama: ditambah
            . ",,\n"                      // baris kosong: dilewati
        ));

        $this->assertSame('success', $import->status);
        $this->assertSame('4 baris: 2 persediaan baru, 2 stok ditambahkan.', $import->keterangan);

        $lama->refresh();
        $this->assertSame([7, 'BUAH', null], [$lama->stock, $lama->satuan, $lama->import_status_id], 'barang lama tidak diberi import_status_id');

        $amplop = Barang::where('nama_barang', 'Amplop Coklat')->sole();
        $this->assertSame([14, 'LEMBAR', $import->id], [$amplop->stock, $amplop->satuan, $amplop->import_status_id]);

        $lakban = Barang::where('nama_barang', 'Lakban')->sole();
        $this->assertSame([3, 'Pcs', $import->id], [$lakban->stock, $lakban->satuan, $lakban->import_status_id]);
    }

    public function test_spasi_berlebih_di_nama_tidak_membuat_persediaan_ganda(): void
    {
        $amplop = $this->barang('AMPLOP COKLAT KECIL', 10);
        $isolasi = $this->barang('ISOLASI  1 X 72', 1); // spasi ganda di database pun tetap cocok

        $import = $this->jalankan($this->importDari(
            "nama_barang,satuan,jumlah\n"
            . "AMPLOP COKLAT  KECIL,BUAH,5\n"          // spasi ganda di tengah
            . "amplop\u{00A0}coklat kecil ,BUAH,1\n"  // non-breaking space + spasi di akhir
            . "ISOLASI 1 X 72,ROLL,2\n"
            . "BATU  BATERAI   AA,BUAH,4\n"            // baru: disimpan dengan spasi tunggal
        ));

        $this->assertSame('success', $import->status);
        $this->assertSame('4 baris: 1 persediaan baru, 3 stok ditambahkan.', $import->keterangan);
        $this->assertSame(16, $amplop->fresh()->stock);
        $this->assertSame(3, $isolasi->fresh()->stock);
        $this->assertSame(4, Barang::where('nama_barang', 'BATU BATERAI AA')->sole()->stock);
        $this->assertSame(3, Barang::count());
    }

    public function test_kolom_alternatif_stok_dan_jumlah(): void
    {
        $import = $this->jalankan($this->importDari("nama,jumlah,satuan\nMap Plastik,6,BUAH\n"));

        $this->assertSame('success', $import->status);
        $this->assertSame(6, Barang::where('nama_barang', 'Map Plastik')->sole()->stock);
    }

    public function test_satu_baris_tidak_valid_membatalkan_seluruh_file(): void
    {
        $lama = $this->barang('Stapler', 2);

        $import = $this->jalankan($this->importDari(
            "nama_barang,stock,satuan\n"
            . "Stapler,5,BUAH\n"
            . ",3,BUAH\n"
            . "Amplop,-1,BUAH\n"
            . "Spidol,dua,BUAH\n"
            . "Pulpen,1.5,BUAH\n"
            . "Kertas,,RIM\n"
        ));

        $this->assertSame('error', $import->status);
        $this->assertSame(
            "Tidak ada data yang disimpan. Perbaiki baris berikut lalu unggah ulang:\n"
            . "Baris 3: nama persediaan kosong.\n"
            . "Baris 4 (Amplop): jumlah harus bilangan bulat 0 atau lebih.\n"
            . "Baris 5 (Spidol): jumlah harus bilangan bulat 0 atau lebih.\n"
            . "Baris 6 (Pulpen): jumlah harus bilangan bulat 0 atau lebih.\n"
            . "Baris 7 (Kertas): jumlah harus bilangan bulat 0 atau lebih.",
            $import->keterangan
        );
        $this->assertSame(2, $lama->fresh()->stock, 'baris valid pun tidak disimpan');
        $this->assertSame(1, Barang::count());
    }

    public function test_file_tanpa_data_gagal(): void
    {
        $import = $this->jalankan($this->importDari("nama_barang,stock,satuan\n"));

        $this->assertSame('error', $import->status);
        $this->assertStringContainsString('File tidak berisi data persediaan', $import->keterangan);
    }

    public function test_galat_tak_terduga_menandai_impor_gagal(): void
    {
        $import = $this->importDari("nama_barang,stock,satuan\n");

        (new ImportPersediaan($import))->failed(new RuntimeException('File rusak'));

        $this->assertSame(['error', 'Impor gagal diproses: File rusak'], [$import->fresh()->status, $import->fresh()->keterangan]);
    }

    // ─── Riwayat impor ───

    public function test_riwayat_impor_menampilkan_status_terbaru(): void
    {
        $admin = $this->user('admin');
        $gagal = ImportStatus::create(['user_id' => $admin->id, 'nama_file' => 'lama.xlsx', 'path' => 'imports/a.xlsx', 'status' => 'error', 'keterangan' => 'Baris 2: nama persediaan kosong.']);
        $gagal->forceFill(['created_at' => now()->subHour(), 'updated_at' => now()->subHour()])->save();
        ImportStatus::create(['user_id' => $admin->id, 'nama_file' => 'baru.xlsx', 'path' => 'imports/b.xlsx', 'status' => 'processing']);

        $this->actingAs($admin)->getJson(route('barang.import.riwayat'))
            ->assertOk()
            ->assertJsonPath('data.0.nama_file', 'baru.xlsx')
            ->assertJsonPath('data.0.status_label', 'Diproses')
            ->assertJsonPath('data.0.oleh', $admin->username)
            ->assertJsonPath('data.1.status', 'error')
            ->assertJsonPath('data.1.status_label', 'Gagal')
            ->assertJsonPath('data.1.keterangan', 'Baris 2: nama persediaan kosong.');
    }

    public function test_halaman_kelola_punya_tombol_riwayat_impor(): void
    {
        $this->actingAs($this->user('admin'))->get(route('barang.index'))
            ->assertOk()
            ->assertSee('Riwayat Impor')
            ->assertSee('onsubmit="kirimImport(event)"', false);
    }
}
