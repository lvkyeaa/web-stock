<?php

namespace App\Jobs;

use App\Imports\BarangImport;
use App\Models\Barang;
use App\Models\ImportStatus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

// Impor persediaan dari Excel, berjalan di queue.
// Identitas barang = nama (tanpa beda huruf besar/kecil & spasi berlebih): nama sudah ada → stok ditambah, belum ada → persediaan baru.
// Semua-atau-tidak-sama-sekali: satu baris tidak valid membatalkan seluruh file.
class ImportPersediaan implements ShouldQueue
{
    use Queueable;

    // Tidak diulang otomatis, agar stok tidak pernah tertambah dua kali dari file yang sama
    public int $tries = 1;

    public int $timeout = 300;

    public function __construct(public ImportStatus $importStatus)
    {
    }

    public function handle(): void
    {
        $this->importStatus->update(['status' => 'processing']);

        $rows = Excel::toArray(new BarangImport, $this->importStatus->path, 'local')[0] ?? [];

        // ─── Tahap 1: periksa semua baris dulu, belum ada yang disimpan ───
        $valid = [];
        $galat = [];

        foreach ($rows as $i => $row) {
            $baris = $i + 2; // baris 1 di Excel = judul kolom
            $nama = $this->rapikanNama((string) ($row['nama_barang'] ?? $row['nama'] ?? ''));
            $jumlah = $row['stock'] ?? $row['stok'] ?? $row['jumlah'] ?? null;
            $satuan = trim((string) ($row['satuan'] ?? ''));

            // Baris kosong (mis. sisa format di akhir sheet) dilewati
            if ($nama === '' && ($jumlah === null || $jumlah === '') && $satuan === '') {
                continue;
            }
            if ($nama === '') {
                $galat[] = "Baris {$baris}: nama persediaan kosong.";
                continue;
            }
            if (! $this->bilanganBulatNonNegatif($jumlah)) {
                $galat[] = "Baris {$baris} ({$nama}): jumlah harus bilangan bulat 0 atau lebih.";
                continue;
            }

            $valid[] = ['nama' => $nama, 'jumlah' => (int) $jumlah, 'satuan' => $satuan !== '' ? $satuan : 'Pcs'];
        }

        if ($galat) {
            $this->gagal('Tidak ada data yang disimpan. Perbaiki baris berikut lalu unggah ulang:' . "\n" . implode("\n", $galat));

            return;
        }
        if (! $valid) {
            $this->gagal('File tidak berisi data persediaan. Baris pertama harus berisi judul kolom: nama_barang, stock, satuan.');

            return;
        }

        // ─── Tahap 2: simpan semua dalam satu transaksi ───
        [$dibuat, $ditambah] = DB::transaction(function () use ($valid) {
            $dibuat = 0;
            $ditambah = 0;

            // Indeks nama → barang. Nama di database ikut dirapikan, jadi "AMPLOP COKLAT  KECIL" (dua spasi) di Excel
            // tetap cocok dengan "AMPLOP COKLAT KECIL" dan tidak membuat persediaan ganda
            $indeks = Barang::get(['id', 'nama_barang', 'stock'])->keyBy(fn (Barang $b) => mb_strtolower($this->rapikanNama($b->nama_barang)));

            foreach ($valid as $row) {
                $kunci = mb_strtolower($row['nama']);
                $barang = $indeks->get($kunci);

                if ($barang) {
                    // Atomik (stock = stock + n), aman bersamaan dengan persetujuan pengajuan yang memotong stok
                    $barang->increment('stock', $row['jumlah']);
                    $ditambah++;
                } else {
                    // Nama yang sama muncul lagi di baris berikutnya akan menambah stok barang baru ini
                    $indeks[$kunci] = Barang::create([
                        'nama_barang'      => $row['nama'],
                        'stock'            => $row['jumlah'],
                        'satuan'           => $row['satuan'],
                        'import_status_id' => $this->importStatus->id,
                    ]);
                    $dibuat++;
                }
            }

            return [$dibuat, $ditambah];
        });

        $this->importStatus->update([
            'status'     => 'success',
            'keterangan' => count($valid) . " baris: {$dibuat} persediaan baru, {$ditambah} stok ditambahkan.",
        ]);
    }

    // Galat tak terduga (file rusak, format tidak dikenali, dsb.)
    public function failed(Throwable $e): void
    {
        $this->gagal('Impor gagal diproses: ' . $e->getMessage());
    }

    // Spasi di awal/akhir dibuang dan spasi berurutan (termasuk non-breaking space dari salinan web/Word) dijadikan satu
    private function rapikanNama(string $nama): string
    {
        return trim(preg_replace('/[\s\x{00A0}]+/u', ' ', $nama));
    }

    private function gagal(string $keterangan): void
    {
        $this->importStatus->update(['status' => 'error', 'keterangan' => $keterangan]);
    }

    // Nilai dari Excel bisa int, float (5.0), atau teks ("5")
    private function bilanganBulatNonNegatif(mixed $nilai): bool
    {
        if (is_int($nilai)) {
            return $nilai >= 0;
        }
        if (is_float($nilai)) {
            return $nilai >= 0 && floor($nilai) === $nilai;
        }

        return is_string($nilai) && ctype_digit(trim($nilai));
    }
}
