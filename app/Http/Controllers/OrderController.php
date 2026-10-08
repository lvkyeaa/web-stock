<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Riwayat;
use App\Models\Team;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// Controller pengajuan barang untuk role admin (semua pengajuan, setujui/tolak, pengajuan sendiri langsung disetujui) maupun customer (pengajuan sendiri).
// Aksi khusus satu peran dibatasi di route; hak akses per pengajuan (milik sendiri) dicek di sini.
class OrderController extends Controller
{
    public const STATUS = ['pending' => 'Pending', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak', 'dibatalkan' => 'Dibatalkan'];

    // Admin: halaman persetujuan (semua pengajuan); customer: Pengajuan Saya
    public function index(Request $request)
    {
        if ($request->user()->hasRole('admin')) {
            return view('admin.transaksi.index', ['initial' => $this->kondisiAwal($request, 'semua')]);
        }

        return $this->saya($request);
    }

    // Pengajuan milik user yang login (customer: halaman pengajuan utamanya; admin: pengajuan yang ia buat sendiri)
    public function saya(Request $request)
    {
        return view('customer.pengajuan.index', ['initial' => $this->kondisiAwal($request, 'saya')]);
    }

    // Hanya kerangka halaman; daftar diambil lewat data() (JSON). Kondisi awal dari query string (tetap saat reload)
    private function kondisiAwal(Request $request, string $lingkup): array
    {
        return [
            'status'  => array_key_exists((string) $request->status, self::STATUS) ? $request->status : '',
            'q'       => mb_substr(trim((string) $request->q), 0, 100),
            'page'    => max(1, (int) $request->page),
            'lingkup' => $lingkup, // saya = pengajuan sendiri, semua = semua pengajuan (khusus admin)
            'dataUrl' => route('pengajuan.data'),
            'csrf'    => csrf_token(),
        ];
    }

    // API JSON: daftar pengajuan per halaman, dengan filter status & pencarian, beserta aksi yang boleh dilakukan
    public function data(Request $request)
    {
        $request->validate([
            'lingkup' => 'nullable|in:saya,semua',
            'status'  => 'nullable|in:' . implode(',', array_keys(self::STATUS)),
            'q'       => 'nullable|string|max:100',
            'page'    => 'nullable|integer|min:1',
        ]);

        $user = $request->user();
        $isAdmin = $user->hasRole('admin');
        $semua = $request->lingkup === 'semua';
        abort_if($semua && ! $isAdmin, 403);

        $orders = Order::with(['user:id,username,name', 'items'])
            ->when(! $semua, fn ($query) => $query->where('user_id', $user->id))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            // Cari kode pengajuan atau nama persediaan; di daftar semua pengajuan juga nama/username pemohon
            ->when($request->filled('q'), function ($query) use ($request, $semua) {
                $kata = '%' . trim($request->q) . '%';
                $query->where(fn ($w) => $w->where('code', 'like', $kata)
                    ->orWhereHas('items', fn ($item) => $item->where('nama_barang', 'like', $kata))
                    ->when($semua, fn ($w) => $w->orWhereHas('user', fn ($u) => $u->where('username', 'like', $kata)->orWhere('name', 'like', $kata))));
            })
            ->latest()
            ->paginate(10);

        return response()->json([
            'data' => $orders->getCollection()->map(fn (Order $order) => [
                'id'           => $order->id,
                'code'         => $order->code,
                'status'       => $order->status,
                'status_label' => self::STATUS[$order->status] ?? $order->status,
                'alasan'       => $order->alasan,
                'dibuat'       => $order->created_at->tanggalJam(),
                'pemohon'      => $order->user->username ?? $order->user->name ?? '-',                'items'        => $order->items->map->only(['nama_barang', 'satuan', 'jumlah'])->values(),
                // Aksi yang boleh dilakukan pada baris ini (null = tidak ada tombolnya)
                'pdf_url'      => $order->status === 'disetujui' ? route('pengajuan.cetak-pdf', $order) : null,
                'batal_url'    => $order->status === 'pending' && $order->user_id === $user->id ? route('pengajuan.batal', $order) : null,
                'status_url'   => $order->status === 'pending' && $isAdmin && $semua ? route('pengajuan.update-status', $order) : null,
            ]),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page'    => $orders->lastPage(),
                'from'         => $orders->firstItem(),
                'to'           => $orders->lastItem(),
                'total'        => $orders->total(),
            ],
        ]);
    }

    // Checkout: isi keranjang menjadi satu pengajuan (order).
    // Customer: pending, stok dipesan sampai admin menyetujui. Admin: langsung disetujui & stok langsung dipotong.
    public function store(Request $request)
    {
        $request->validate([
            'team_id'                    => 'required|exists:teams,id',
            'person_responsible_user_id' => 'nullable|string',
        ], [
            'team_id.required' => 'Pilih tim terlebih dahulu.',
        ]);

        // Tim yang punya ketua: penanggung jawab wajib dipilih dan harus salah satu ketua tim itu.
        // Tim tanpa ketua: penanggung jawab dikosongkan (nama di PDF dibiarkan kosong)
        $chiefs = Team::find($request->team_id)->chiefs();
        if ($chiefs->exists()) {
            if (! $request->filled('person_responsible_user_id')) {
                return response()->json(['message' => 'Pilih ketua tim/penanggung jawab terlebih dahulu.'], 422);
            }
            if (! $chiefs->whereKey($request->person_responsible_user_id)->exists()) {
                return response()->json(['message' => 'Ketua tim/penanggung jawab tidak sesuai dengan tim yang dipilih.'], 422);
            }
        } else {
            $request->merge(['person_responsible_user_id' => null]);
        }

        $user = $request->user();
        $isAdmin = $user->hasRole('admin');

        $hasil = DB::transaction(function () use ($request, $user, $isAdmin) {
            // Satu checkout per user dalam satu waktu: submit ganda menunggu di sini, lalu mendapati keranjang kosong
            User::whereKey($user->id)->lockForUpdate()->first();

            // Kunci baris barang di keranjang: checkout lain untuk barang yang sama menunggu di sini,
            // lalu menghitung stok tersedia setelah pengajuan ini tercatat (tidak bisa dua orang mendapat unit terakhir).
            // PENTING: semua pembacaan sebelum kunci barang didapat harus locking read (lockForUpdate). Di MySQL
            // (REPEATABLE READ) snapshot transaksi dibuat pada SELECT biasa pertama; jika itu terjadi sebelum menunggu kunci,
            // keranjang() di bawah tidak melihat pengajuan yang baru di-commit checkout lain, dan unit terakhir bisa diajukan dua kali.
            Barang::whereKey($user->cartItems()->lockForUpdate()->pluck('barang_id'))->lockForUpdate()->get();

            $keranjang = $user->keranjang();

            if ($keranjang->isEmpty()) {
                return 'Keranjang kamu masih kosong!';
            }

            // Pengajuan hanya dibuat jika semua barang muat di stok tersedia; keranjang tidak diubah
            $kurang = $keranjang->reject(fn (CartItem $item) => $item->cukup());
            if ($kurang->isNotEmpty()) {
                return 'Stok tidak mencukupi untuk: ' . $kurang->map(fn (CartItem $item) => "{$item->barang->nama_barang} (tersedia " . max(0, $item->barang->tersedia) . ')')->implode(', ')
                    . '. Kurangi jumlah atau hapus persediaannya, lalu kirim ulang.';
            }

            $order = $user->orders()->create([
                'code'                       => Order::buatKode(),
                'status'                     => $isAdmin ? 'disetujui' : 'pending',
                'team_id'                  => $request->team_id,
                'person_responsible_user_id' => $request->person_responsible_user_id,
            ]);

            $order->items()->createMany($keranjang->map(fn (CartItem $item) => [
                'barang_id'   => $item->barang_id,
                'stock_id'    => $item->barang->stock_id,
                'nama_barang' => $item->barang->nama_barang,
                'satuan'      => $item->barang->satuan,
                'jumlah'      => $item->jumlah,
            ])->all());

            if ($isAdmin) {
                // Baris barang sudah dikunci di atas dan jumlahnya muat di stok tersedia, jadi stok tidak bisa minus
                foreach ($keranjang as $item) {
                    $item->barang->decrement('stock', $item->jumlah);
                }

                Riwayat::create([
                    'order_id'          => $order->id,
                    'actor_id'          => $user->id,
                    'status_sebelumnya' => null,
                    'status_sesudah'    => 'disetujui',
                    'catatan'           => 'Pengajuan admin, langsung disetujui.',
                ]);

                $order->beriNomor(); // nomor surat PDF tetap sejak disetujui
            }

            $user->cartItems()->delete();

            return $order;
        });

        // Dipanggil dari halaman keranjang lewat fetch (JSON)
        if (is_string($hasil)) {
            // Stok tersedia terbaru tiap baris, agar peringatan di halaman keranjang langsung sesuai
            return response()->json([
                'message' => $hasil,
                'items'   => $user->keranjang()->map(fn (CartItem $item) => $item->untukKeranjang())->values(),
            ], 422);
        }

        $pesan = $isAdmin
            ? "Pengajuan {$hasil->code} disetujui dan stok telah dipotong."
            : "Pengajuan {$hasil->code} berhasil dikirim!";

        // Halaman keranjang lalu pindah ke Pengajuan Saya (url); pesan sukses tampil di sana lewat flash session
        session()->flash('success', $pesan);

        return response()->json([
            'message'          => $pesan,
            'code'             => $hasil->code,
            'url'              => route($isAdmin ? 'pengajuan.saya' : 'pengajuan.index'),
            'jumlah_keranjang' => 0,
        ]);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:disetujui,ditolak',
            'alasan' => 'nullable|string|max:500',
        ]);

        $error = DB::transaction(function () use ($request, $order) {
            // Kunci order agar dua admin tidak memproses pengajuan yang sama bersamaan
            $order = Order::whereKey($order->id)->lockForUpdate()->first();

            if ($order->status !== 'pending') {
                return 'Transaksi ini sudah diproses sebelumnya.';
            }

            if ($request->status === 'disetujui') {
                // Kunci baris barang agar stok tidak bisa minus karena persetujuan bersamaan
                $barang = Barang::whereKey($order->items->pluck('barang_id'))->lockForUpdate()->get()->keyBy('id');

                foreach ($order->items as $item) {
                    $stok = $barang->get($item->barang_id);

                    if (! $stok) {
                        return "Persediaan '{$item->nama_barang}' sudah dihapus dari inventaris.";
                    }
                    if ($stok->stock < $item->jumlah) {
                        return "Stok persediaan '{$item->nama_barang}' tidak mencukupi. Sisa stok: {$stok->stock}, diminta: {$item->jumlah}.";
                    }
                }

                foreach ($order->items as $item) {
                    $barang->get($item->barang_id)->decrement('stock', $item->jumlah);
                }
            }

            Riwayat::create([
                'order_id'          => $order->id,
                'actor_id'          => auth()->id(),
                'status_sebelumnya' => $order->status,
                'status_sesudah'    => $request->status,
                'catatan'           => $request->alasan,
            ]);

            $order->update([
                'status' => $request->status,
                'alasan' => $request->alasan,
            ]);

            if ($request->status === 'disetujui') {
                $order->beriNomor(); // nomor surat PDF tetap sejak disetujui
            }

            return null;
        });

        $pesan = $error ?? ($request->status === 'disetujui'
            ? 'Permintaan persediaan berhasil disetujui dan stok telah dipotong.'
            : 'Permintaan persediaan telah ditolak.');

        // Dari halaman persetujuan (fetch) cukup JSON tanpa reload; form biasa tetap redirect dengan pesan
        if ($request->expectsJson()) {
            return response()->json(['message' => $pesan], $error ? 422 : 200);
        }

        return back()->with($error ? 'error' : 'success', $pesan);
    }

    // Pemohon membatalkan pengajuannya sendiri selama masih pending (dipanggil lewat fetch, respons JSON).
    // Status jadi 'dibatalkan' (riwayat tetap ada); stok yang dipesan otomatis lepas karena reservasi hanya menghitung pending.
    public function batal(Request $request, Order $order)
    {
        // 404 agar keberadaan pengajuan milik user lain tidak terungkap
        abort_unless($order->user_id === $request->user()->id, 404);

        $galat = DB::transaction(function () use ($request, $order) {
            // Kunci baris pengajuan: jika admin menyetujui/menolak bersamaan, hanya salah satu yang berhasil
            $order = Order::whereKey($order->id)->lockForUpdate()->first();

            if ($order->status !== 'pending') {
                return 'Pengajuan ini sudah diproses admin sehingga tidak bisa dibatalkan.';
            }

            Riwayat::create([
                'order_id'          => $order->id,
                'actor_id'          => $request->user()->id,
                'status_sebelumnya' => 'pending',
                'status_sesudah'    => 'dibatalkan',
                'catatan'           => 'Dibatalkan oleh pemohon.',
            ]);

            $order->update(['status' => 'dibatalkan']);

            return null;
        });

        if ($galat) {
            // Status terbaru ikut dikirim agar kartu di halaman langsung sesuai
            return response()->json(['message' => $galat, 'status' => $order->fresh()->status], 422);
        }

        return response()->json(['message' => "Pengajuan {$order->code} dibatalkan.", 'status' => 'dibatalkan']);
    }

    public function cetakPdf(Request $request, Order $order)
    {
        // Customer hanya boleh mencetak miliknya; 404 agar keberadaan pengajuan milik user lain tidak terungkap
        abort_unless($request->user()->hasRole('admin') || $order->user_id === $request->user()->id, 404);

        if ($order->status !== 'disetujui') {
            return back()->with('error', 'Cetak dokumen hanya tersedia untuk transaksi yang telah disetujui (ACC).');
        }

        $order->load(['user', 'team', 'personResponsible', 'items']);

        return Pdf::loadView('pdf.bukti-pengajuan', ['pengajuan' => $order, 'nomorSurat' => $order->nomorSurat()])
            ->setPaper('a4', 'portrait')
            ->stream('Permintaan_ATK_' . $order->code . '.pdf');
    }
}
