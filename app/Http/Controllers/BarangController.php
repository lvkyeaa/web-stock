<?php

namespace App\Http\Controllers;

use App\Exports\TemplateImportPersediaan;
use App\Jobs\ImportPersediaan;
use App\Models\Barang;
use App\Models\ImportStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

// Controller barang & stok untuk role admin (kelola) maupun customer (katalog). Aksi ubah data khusus admin (dibatasi di route).
class BarangController extends Controller
{
    // Pilihan urutan daftar: terbaru ditambahkan, paling sering diminta (90 hari / sepanjang waktu), atau nama A–Z
    private const URUTAN = ['terbaru', 'populer', 'populer_total', 'nama'];

    // Admin: halaman kelola barang (tabel); customer: katalog
    public function index(Request $request)
    {
        if ($request->user()->hasRole('admin')) {
            return view('admin.barang.index', ['initial' => $this->kondisiAwal($request, 10, 'populer')]);
        }

        return $this->katalog($request);
    }

    // Katalog (kartu barang, tambah ke keranjang) untuk admin maupun customer
    public function katalog(Request $request)
    {
        return view('customer.katalog.index', ['initial' => $this->kondisiAwal($request, 12, 'populer')]);
    }

    // Hanya kerangka halaman; daftar barang diambil lewat data() (JSON)
    // Kondisi awal dari query string (agar posisi tetap saat reload / kembali setelah submit form)
    private function kondisiAwal(Request $request, int $perPage, string $urutBawaan): array
    {
        return [
            'search'    => mb_substr(trim((string) $request->search), 0, 100),
            'stokHabis' => $request->boolean('stok_habis'),
            'page'      => max(1, (int) $request->page),
            'urut'      => in_array($request->urut, self::URUTAN, true) ? $request->urut : $urutBawaan,
            'urutBawaan' => $urutBawaan, // tidak ditulis di URL jika sama dengan bawaan
            'perPage'   => $perPage, // tabel admin 10 baris, grid katalog 12 kartu (pas 3/4 kolom)
            'dataUrl'   => route('barang.data'),
        ];
    }

    // API JSON: daftar barang per halaman sesuai pencarian & filter stok habis
    public function data(Request $request)
    {
        $request->validate([
            'search'     => 'nullable|string|max:100',
            'stok_habis' => 'nullable|boolean',
            'page'       => 'nullable|integer|min:1',
            'per_page'   => 'nullable|integer|in:10,12',
            'urut'       => 'nullable|in:' . implode(',', self::URUTAN),
            'kode'       => 'nullable|string|max:50', // cari persis berdasarkan kode (peringatan kode sudah dipakai di dialog Tambah)
        ]);

        $barang = Barang::withDipesan()
            ->withPopularitas()
            ->withCount('orderItems')
            ->when($request->filled('search'), fn ($query) => $query->where('nama_barang', 'like', '%' . $request->search . '%'))
            ->when($request->filled('kode'), fn ($query) => $query->where('stock_id', trim($request->kode)))
            // Barang tanpa stok tersedia (habis, atau semuanya sudah diajukan) disembunyikan kecuali filter "tampilkan stok habis" aktif
            ->when(! $request->boolean('stok_habis'), fn ($query) => $query->whereTersedia())
            ->when($request->urut === 'populer', fn ($query) => $query->orderByDesc('diminta_90_hari')->orderByDesc('diminta_total')->orderBy('nama_barang'))
            ->when($request->urut === 'populer_total', fn ($query) => $query->orderByDesc('diminta_total')->orderByDesc('diminta_90_hari')->orderBy('nama_barang'))
            ->when($request->urut === 'nama', fn ($query) => $query->orderBy('nama_barang'))
            ->when(! in_array($request->urut, ['populer', 'populer_total', 'nama'], true), fn ($query) => $query->latest())
            ->paginate($request->integer('per_page') ?: ($request->user()->hasRole('admin') ? 10 : 12));

        return response()->json([
            'data' => $barang->getCollection()->map(fn (Barang $item) => [
                'id'          => $item->id,
                'stock_id'    => $item->stock_id,
                'nama_barang' => $item->nama_barang,
                'stock'       => $item->stock,
                'dipesan'     => (int) $item->dipesan,
                'tersedia'    => max(0, $item->tersedia),
                'satuan'      => $item->satuan,
                'foto_url'    => $item->foto_url,
                // Persediaan yang pernah diajukan (status apa pun) tidak bisa dihapus, lihat destroy()
                'bisa_dihapus' => $item->order_items_count === 0,
                // Popularitas: jumlah pengajuan yang disetujui
                'diminta_90_hari' => $item->diminta_90_hari,
                'diminta_total'   => $item->diminta_total,
            ]),
            'meta' => [
                'current_page' => $barang->currentPage(),
                'last_page'    => $barang->lastPage(),
                'from'         => $barang->firstItem(),
                'to'           => $barang->lastItem(),
                'total'        => $barang->total(),
            ],
        ]);
    }

    // Tambah barang baru; jika kode (atau nama, bila kode kosong) sudah ada, stoknya ditambahkan
    public function store(Request $request)
    {
        // barang_id = dipilih dari daftar persediaan yang ada; tanpa barang_id = buat persediaan baru
        $request->validate([
            'barang_id'   => 'nullable|uuid|exists:barang,id',
            'stock_id'    => 'nullable|string|max:50',
            'nama_barang' => 'required_without:barang_id|nullable|string|max:255',
            'satuan'      => 'required_without:barang_id|nullable|string|max:50',
            'stock'       => 'required|integer|min:1',
            'foto'        => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $kode = $request->filled('stock_id') ? trim($request->stock_id) : null;
        $existingBarang = $request->filled('barang_id')
            ? Barang::find($request->barang_id)
            : $this->cariBarang($kode, (string) $request->nama_barang);

        if ($existingBarang) {
            // Nama baru yang ternyata sudah ada tidak dibuat ganda: stoknya ditambah. Kode & foto ikut diisi
            // jika persediaan lama belum punya.
            $tambahan = ['stock_id' => $existingBarang->stock_id ?? $kode];
            if (! $existingBarang->foto && $request->hasFile('foto')) {
                $tambahan['foto'] = $request->file('foto')->store('barang', 'public');
            }
            $existingBarang->increment('stock', (int) $request->stock, $tambahan);

            return response()->json(['message' => "Stok persediaan '{$existingBarang->nama_barang}' berhasil ditambahkan."]);
        }

        try {
            Barang::create([
                'stock_id'    => $kode,
                'nama_barang' => Barang::rapikanNama($request->nama_barang),
                'stock'       => (int) $request->stock,
                'satuan'      => trim($request->satuan),
                'foto'        => $request->hasFile('foto') ? $request->file('foto')->store('barang', 'public') : null,
            ]);
        } catch (UniqueConstraintViolationException) {
            // Nama / kode baru saja dibuat admin lain di sela pengecekan di atas (unique index di database)
            return response()->json(['message' => 'Persediaan dengan nama atau kode ini baru saja ditambahkan. Muat ulang daftar lalu coba lagi.'], 422);
        }

        return response()->json(['message' => 'Persediaan baru berhasil ditambahkan.']);
    }

    // Edit data barang (kode, nama, satuan, foto). Stok diubah lewat updateStock.
    public function update(Request $request, Barang $barang)
    {
        $request->validate([
            'stock_id'    => 'nullable|string|max:50|unique:barang,stock_id,' . $barang->id,
            'nama_barang' => 'required|string|max:255',
            'satuan'      => 'required|string|max:50',
            'foto'        => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        // Nama unik (tanpa beda huruf besar/kecil & spasi berlebih), disimpan dalam bentuk rapi seperti saat dibuat
        $sama = Barang::cariNama($request->nama_barang);
        if ($sama && $sama->id !== $barang->id) {
            throw ValidationException::withMessages(['nama_barang' => "Nama ini sudah dipakai persediaan '{$sama->nama_barang}'."]);
        }

        $data = $request->only('stock_id', 'satuan') + ['nama_barang' => Barang::rapikanNama($request->nama_barang)];

        if ($request->hasFile('foto')) {
            if ($barang->foto && Storage::disk('public')->exists($barang->foto)) {
                Storage::disk('public')->delete($barang->foto);
            }

            $data['foto'] = $request->file('foto')->store('barang', 'public');
        }

        $barang->update($data);

        return response()->json(['message' => 'Persediaan berhasil diperbarui.']);
    }

    // Ubah stok: tambah (barang masuk), kurang (koreksi/keluar), atau set ke angka tertentu (stock opname)
    public function updateStock(Request $request, Barang $barang)
    {
        $request->validate([
            'operasi' => 'required|in:tambah,kurang,set',
            'jumlah'  => 'required|integer|min:' . ($request->operasi === 'set' ? 0 : 1),
        ]);

        $jumlah = (int) $request->jumlah;

        // Kunci baris barang agar tidak bertabrakan dengan pemotongan stok saat admin menyetujui pengajuan
        $hasil = DB::transaction(function () use ($barang, $request, $jumlah) {
            $barang = Barang::whereKey($barang->id)->lockForUpdate()->withDipesan()->first();

            $stokBaru = match ($request->operasi) {
                'tambah' => $barang->stock + $jumlah,
                'kurang' => $barang->stock - $jumlah,
                'set'    => $jumlah,
            };

            if ($stokBaru < 0) {
                return null;
            }

            $stokLama = $barang->stock;
            $barang->update(['stock' => $stokBaru]);

            return [
                'message' => "Stok '{$barang->nama_barang}' diubah: {$stokLama} → {$stokBaru}.",
                // Tetap disimpan (hasil hitung fisik harus bisa dicatat), tapi admin diberi tahu
                'warning' => $stokBaru < (int) $barang->dipesan
                    ? "Perhatian: {$barang->dipesan} {$barang->satuan} sudah diajukan di pengajuan pending, lebih dari stok baru. Sebagian pengajuan tidak akan bisa disetujui."
                    : null,
            ];
        });

        if (! $hasil) {
            return response()->json([
                'message' => "Stok '{$barang->nama_barang}' tidak boleh kurang dari 0. Sisa stok: {$barang->fresh()->stock}.",
            ], 422);
        }

        return response()->json($hasil);
    }

    // Persediaan yang pernah diajukan (status apa pun) tidak boleh dihapus, agar riwayat pengajuan tetap utuh
    public function destroy(Barang $barang)
    {
        $galat = DB::transaction(function () use ($barang) {
            // Kunci baris barang: checkout yang sedang berjalan juga mengunci baris ini, jadi pengecekan di bawah
            // melihat pengajuan yang baru masuk dan tidak ada pengajuan yang lolos di sela pengecekan & penghapusan
            $barang = Barang::whereKey($barang->id)->lockForUpdate()->firstOrFail();

            $jumlahPengajuan = $barang->orderItems()->count();
            if ($jumlahPengajuan > 0) {
                return "Persediaan '{$barang->nama_barang}' tidak bisa dihapus karena sudah diajukan di {$jumlahPengajuan} pengajuan.";
            }

            $barang->delete();

            return null;
        });

        if ($galat) {
            return response()->json(['message' => $galat], 422);
        }

        if ($barang->foto && Storage::disk('public')->exists($barang->foto)) {
            Storage::disk('public')->delete($barang->foto);
        }

        return response()->json(['message' => "Persediaan '{$barang->nama_barang}' berhasil dihapus."]);
    }

    // Unggah Excel: file disimpan & diproses di latar belakang (job ImportPersediaan), statusnya dilacak di import_statuses
    public function importExcel(Request $request)
    {
        $request->validate([
            'file_excel' => 'required|file|mimes:xlsx,xls,csv|extensions:xlsx,xls,csv|max:10240',
        ]);

        $file = $request->file('file_excel');

        // Ekstensi asli dipertahankan agar pembaca Excel mengenali jenis filenya (csv bisa terdeteksi sebagai .txt)
        $import = ImportStatus::create([
            'user_id'   => $request->user()->id,
            'nama_file' => $file->getClientOriginalName(),
            'path'      => $file->storeAs('imports', Str::uuid() . '.' . strtolower($file->getClientOriginalExtension()), 'local'),
            'status'    => 'start',
        ]);

        ImportPersediaan::dispatch($import);

        return response()->json([
            'message' => "File '{$import->nama_file}' diterima dan sedang diproses. Cek statusnya di Riwayat Impor.",
        ], 202);
    }

    // Unduh template Excel impor persediaan (judul kolom + satu baris contoh)
    public function templateImport()
    {
        return Excel::download(new TemplateImportPersediaan, 'template_impor_persediaan.xlsx');
    }

    // Daftar unggahan terbaru beserta statusnya (dialog Riwayat Impor)
    public function riwayatImport()
    {
        $imports = ImportStatus::with('user:id,username')->latest()->limit(20)->get();

        return response()->json([
            'data' => $imports->map(fn (ImportStatus $import) => [
                'id'           => $import->id,
                'nama_file'    => $import->nama_file,
                'status'       => $import->status,
                'status_label' => ImportStatus::STATUS[$import->status] ?? $import->status,
                'keterangan'   => $import->keterangan,
                'oleh'         => $import->user?->username,
                'diunggah'     => $import->created_at->tanggalJam(),
                'diperbarui'   => $import->updated_at->tanggalJam(),
            ]),
        ]);
    }

    // Cari berdasarkan kode jika diisi; jika tidak ketemu, berdasarkan nama (tanpa beda huruf besar/kecil & spasi berlebih)
    private function cariBarang(?string $kode, string $nama): ?Barang
    {
        $kode = trim((string) $kode);

        if ($kode !== '' && $barang = Barang::where('stock_id', $kode)->first()) {
            return $barang;
        }

        // Nama sama (tanpa beda huruf besar/kecil & spasi berlebih) dianggap persediaan yang sama
        return Barang::cariNama($nama);
    }
}
