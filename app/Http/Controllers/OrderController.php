<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Riwayat;
use App\Models\Team;
use App\Models\User;
use App\Services\PersetujuanPengajuan;
use App\Services\WhatsApp;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

// Controller pengajuan barang. Persetujuan dua tingkat: ketua tim yang dipilih saat checkout (wajib tanda tangan), lalu admin
// (stok dipotong). Berlaku juga untuk pengajuan admin. Customer & admin: pengajuan sendiri.
// Aksi khusus satu peran dibatasi di route; hak akses per pengajuan (milik sendiri / ketua yang dipilih) dicek di sini.
class OrderController extends Controller
{
    public const STATUS = [
        'menunggu_ketua' => 'Menunggu Ketua Tim',
        'menunggu_admin' => 'Menunggu Admin',
        'disetujui'      => 'Disetujui',
        'ditolak'        => 'Ditolak',
        'dibatalkan'     => 'Dibatalkan',
    ];

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

    // Persetujuan tingkat 1: pengajuan yang memilih user yang login sebagai ketua tim/penanggung jawab
    public function ketua(Request $request)
    {
        return view('admin.transaksi.index', ['initial' => $this->kondisiAwal($request, 'ketua')]);
    }

    // Hanya kerangka halaman; daftar diambil lewat data() (JSON). Kondisi awal dari query string (tetap saat reload)
    private function kondisiAwal(Request $request, string $lingkup): array
    {
        return [
            'status'  => array_key_exists((string) $request->status, self::STATUS) ? $request->status : '',
            'q'       => mb_substr(trim((string) $request->q), 0, 100),
            'page'    => max(1, (int) $request->page),
            'lingkup' => $lingkup, // saya = pengajuan sendiri, ketua = yang memilihnya sebagai ketua tim, semua = semua pengajuan (khusus admin)
            'dataUrl' => route('pengajuan.data'),
            'csrf'    => csrf_token(),
        ];
    }

    // API JSON: daftar pengajuan per halaman, dengan filter status & pencarian, beserta aksi yang boleh dilakukan
    public function data(Request $request)
    {
        $request->validate([
            'lingkup' => 'nullable|in:saya,ketua,semua',
            'status'  => 'nullable|in:' . implode(',', array_keys(self::STATUS)),
            'q'       => 'nullable|string|max:100',
            'page'    => 'nullable|integer|min:1',
        ]);

        $user = $request->user();
        $isAdmin = $user->hasRole('admin');
        $semua = $request->lingkup === 'semua';
        $ketua = $request->lingkup === 'ketua';
        abort_if($semua && ! $isAdmin, 403);

        $orders = Order::with(['user:id,username,name', 'personResponsible:id,name', 'approvals', 'items'])
            ->when($ketua, fn ($query) => $query->where('person_responsible_user_id', $user->id))
            ->when(! $semua && ! $ketua, fn ($query) => $query->where('user_id', $user->id))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            // Cari kode pengajuan atau nama persediaan; di daftar persetujuan (ketua / semua) juga nama/username pemohon
            ->when($request->filled('q'), function ($query) use ($request, $semua, $ketua) {
                $kata = '%' . trim($request->q) . '%';
                $query->where(fn ($w) => $w->where('code', 'like', $kata)
                    ->orWhereHas('items', fn ($item) => $item->where('nama_barang', 'like', $kata))
                    ->when($semua || $ketua, fn ($w) => $w->orWhereHas('user', fn ($u) => $u->where('username', 'like', $kata)->orWhere('name', 'like', $kata))));
            })
            ->latest()
            ->paginate(10);

        return response()->json([
            'data' => $orders->getCollection()->map(function (Order $order) use ($user, $isAdmin, $semua, $ketua) {
                // Alasan penolakan tercatat di order_approvals (tingkat ketua tim / admin)
                $penolakan = $order->approvals->firstWhere('keputusan', 'ditolak');
                $ttdKetua = $order->approvals->firstWhere('level', 'ketua')?->signature_path;

                return [
                    'id'           => $order->id,
                    'code'         => $order->code,
                    'status'       => $order->status,
                    'status_label' => self::STATUS[$order->status] ?? $order->status,
                    'alasan'       => $penolakan?->catatan,
                    'ditolak_oleh' => $penolakan ? ($penolakan->level === 'ketua' ? 'oleh ketua tim' : 'oleh admin') : null,
                    'dibuat'       => $order->created_at->tanggalJam(),
                    'pemohon'      => $order->user->name ?? $order->user->username ?? '-',
                    'ketua'        => $order->personResponsible->name ?? '-',
                    'items'        => $order->items->map->only(['nama_barang', 'satuan', 'jumlah'])->values(),
                    // Aksi yang boleh dilakukan pada baris ini (null = tidak ada tombolnya)
                    'pdf_url'      => $order->status === 'disetujui' ? route('pengajuan.cetak-pdf', $order) : null,
                    // Gambar tanda tangan (null = belum/tidak ada)
                    'ttd_pemohon'  => $order->user_signature ? route('pengajuan.tanda-tangan', [$order, 'pemohon']) : null,
                    'ttd_ketua'    => $ttdKetua ? route('pengajuan.tanda-tangan', [$order, 'ketua']) : null,
                    // Pemohon: selama masih menunggu; admin (daftar semua): selama masih menunggu ketua tim
                    'batal_url'    => (in_array($order->status, Order::MENUNGGU) && $order->user_id === $user->id)
                        || ($semua && $isAdmin && $order->status === 'menunggu_ketua') ? route('pengajuan.batal', $order) : null,
                    'status_url'   => match (true) {
                        $ketua && $order->status === 'menunggu_ketua'             => route('pengajuan.keputusan-ketua', $order),
                        $semua && $isAdmin && $order->status === 'menunggu_admin' => route('pengajuan.update-status', $order),
                        default                                                   => null,
                    },
                ];
            }),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page'    => $orders->lastPage(),
                'from'         => $orders->firstItem(),
                'to'           => $orders->lastItem(),
                'total'        => $orders->total(),
            ],
        ]);
    }

    // Checkout: isi keranjang menjadi satu pengajuan (order) yang menunggu ketua tim. Berlaku untuk customer maupun admin.
    // Stok dipesan sampai pengajuan disetujui admin (stok dipotong), ditolak, atau dibatalkan.
    public function store(Request $request, WhatsApp $whatsApp)
    {
        $request->validate([
            'team_id'                    => 'required|exists:teams,id',
            'person_responsible_user_id' => 'nullable|string',
            'tanda_tangan'               => $this->aturanTandaTangan(),
            // Nomor WhatsApp wajib bagi pemohon yang belum punya nomor (untuk kabar persetujuan)
            'phone'                      => [$request->user()->phone ? 'nullable' : 'required', 'regex:/^62[0-9]{8,13}$/'],
        ], [
            'team_id.required'      => 'Pilih tim terlebih dahulu.',
            'tanda_tangan.required' => 'Tanda tangan dulu sebelum mengirim pengajuan.',
            'phone.required'        => 'Isi nomor WhatsApp terlebih dahulu.',
            'phone.regex'           => 'Nomor WhatsApp harus diawali 62 dan hanya berisi angka (10–15 digit), contoh 6281234567890.',
        ]);

        // Ketua tim wajib dipilih dan harus salah satu ketua tim itu; tim tanpa ketua belum bisa mengajukan
        $chiefs = Team::find($request->team_id)->chiefs();
        if (! $chiefs->exists()) {
            return response()->json(['message' => 'Tim ini belum memiliki ketua tim, jadi belum bisa mengajukan. Hubungi admin.'], 422);
        }
        if (! $request->filled('person_responsible_user_id')) {
            return response()->json(['message' => 'Pilih ketua tim/penanggung jawab terlebih dahulu.'], 422);
        }
        if (! $chiefs->whereKey($request->person_responsible_user_id)->exists()) {
            return response()->json(['message' => 'Ketua tim/penanggung jawab tidak sesuai dengan tim yang dipilih.'], 422);
        }

        $user = $request->user();
        $isAdmin = $user->hasRole('admin');

        $hasil = DB::transaction(function () use ($request, $user) {
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
                'status'                     => 'menunggu_ketua',
                'user_signature'             => Order::simpanTandaTangan($request->tanda_tangan),
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

            $user->cartItems()->delete();

            // Nomor WhatsApp dari halaman keranjang hanya mengisi yang masih kosong, tidak mengganti nomor yang sudah ada
            if (! $user->phone && $request->filled('phone')) {
                $user->update(['phone' => $request->phone]);
            }

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

        $whatsApp->kabariPengajuan($hasil, 'diajukan');

        $pesan = "Pengajuan {$hasil->code} berhasil dikirim dan menunggu persetujuan ketua tim.";

        // Halaman keranjang lalu pindah ke Pengajuan Saya (url); pesan sukses tampil di sana lewat flash session
        session()->flash('success', $pesan);

        return response()->json([
            'message'          => $pesan,
            'code'             => $hasil->code,
            'url'              => route($isAdmin ? 'pengajuan.saya' : 'pengajuan.index'),
            'jumlah_keranjang' => 0,
        ]);
    }

    // Persetujuan tingkat 1 oleh ketua tim yang dipilih saat checkout. Setuju (wajib tanda tangan) → menunggu admin; tolak → selesai.
    public function keputusanKetua(Request $request, Order $order, PersetujuanPengajuan $persetujuan)
    {
        // 404 agar keberadaan pengajuan yang bukan untuknya tidak terungkap
        abort_unless($order->person_responsible_user_id === $request->user()->id, 404);

        $request->validate([
            'status'       => 'required|in:disetujui,ditolak',
            'alasan'       => 'nullable|string|max:500',
            'tanda_tangan' => ['exclude_unless:status,disetujui', ...$this->aturanTandaTangan()],
        ], [
            'tanda_tangan.required' => 'Tanda tangan dulu sebelum menyetujui.',
        ]);

        $error = $persetujuan->ketua($order, $request->user(), $request->status, $request->alasan, $request->tanda_tangan);

        $pesan = $error ?? ($request->status === 'disetujui'
            ? 'Pengajuan disetujui dan diteruskan ke admin.'
            : 'Pengajuan telah ditolak.');

        return response()->json(['message' => $pesan], $error ? 422 : 200);
    }

    // Persetujuan tingkat 2 oleh admin (tanpa tanda tangan): setuju → stok dipotong & diberi nomor surat
    public function updateStatus(Request $request, Order $order, PersetujuanPengajuan $persetujuan)
    {
        $request->validate([
            'status' => 'required|in:disetujui,ditolak',
            'alasan' => 'nullable|string|max:500',
        ]);

        $error = $persetujuan->admin($order, $request->user(), $request->status, $request->alasan);

        $pesan = $error ?? ($request->status === 'disetujui'
            ? 'Permintaan persediaan berhasil disetujui dan stok telah dipotong.'
            : 'Permintaan persediaan telah ditolak.');

        // Dari halaman persetujuan (fetch) cukup JSON tanpa reload; form biasa tetap redirect dengan pesan
        if ($request->expectsJson()) {
            return response()->json(['message' => $pesan], $error ? 422 : 200);
        }

        return back()->with($error ? 'error' : 'success', $pesan);
    }

    // Pemohon membatalkan pengajuannya sendiri selama masih menunggu ketua tim / admin (dipanggil lewat fetch, respons JSON).
    // Status jadi 'dibatalkan' (riwayat tetap ada); stok yang dipesan otomatis lepas karena reservasi hanya menghitung yang menunggu.
    // Admin juga boleh membatalkan pengajuan siapa pun selama masih menunggu ketua tim.
    public function batal(Request $request, Order $order)
    {
        $pemohon = $order->user_id === $request->user()->id;

        // 404 agar keberadaan pengajuan milik user lain tidak terungkap
        abort_unless($pemohon || $request->user()->hasRole('admin'), 404);

        $galat = DB::transaction(function () use ($request, $order, $pemohon) {
            // Kunci baris pengajuan: jika ketua tim / admin menyetujui/menolak bersamaan, hanya salah satu yang berhasil
            $order = Order::whereKey($order->id)->lockForUpdate()->first();

            if ($pemohon && ! in_array($order->status, Order::MENUNGGU)) {
                return 'Pengajuan ini sudah diproses sehingga tidak bisa dibatalkan.';
            }
            if (! $pemohon && $order->status !== 'menunggu_ketua') {
                return 'Admin hanya bisa membatalkan pengajuan yang masih menunggu ketua tim.';
            }

            Riwayat::create([
                'order_id'          => $order->id,
                'actor_id'          => $request->user()->id,
                'status_sebelumnya' => $order->status,
                'status_sesudah'    => 'dibatalkan',
                'catatan'           => $pemohon ? 'Dibatalkan oleh pemohon.' : 'Dibatalkan oleh admin.',
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

    // Gambar tanda tangan pemohon / ketua tim; hanya untuk admin, pemohon, dan ketua tim yang dipilih (lainnya 404)
    public function tandaTangan(Request $request, Order $order, string $jenis)
    {
        $user = $request->user();
        abort_unless($user->hasRole('admin') || in_array($user->id, [$order->user_id, $order->person_responsible_user_id], true), 404);

        $path = $jenis === 'pemohon' ? $order->user_signature : $order->approvalKetua?->signature_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }

    public function cetakPdf(Request $request, Order $order)
    {
        // Customer hanya boleh mencetak miliknya; 404 agar keberadaan pengajuan milik user lain tidak terungkap
        abort_unless($request->user()->hasRole('admin') || $order->user_id === $request->user()->id, 404);

        if ($order->status !== 'disetujui') {
            return back()->with('error', 'Cetak dokumen hanya tersedia untuk transaksi yang telah disetujui (ACC).');
        }

        $order->load(['user', 'team', 'personResponsible', 'approvalKetua', 'items']);

        return Pdf::loadView('pdf.bukti-pengajuan', ['pengajuan' => $order, 'nomorSurat' => $order->nomorSurat()])
            ->setPaper('a4', 'portrait')
            ->stream('Permintaan_ATK_' . $order->code . '.pdf');
    }

    // Tanda tangan dari kanvas: data URL yang benar-benar berisi gambar PNG (maks. ~500 KB)
    public static function aturanTandaTangan(): array
    {
        return ['required', 'string', 'starts_with:data:image/png;base64,', 'max:700000', function ($attribute, $value, $fail) {
            $info = @getimagesizefromstring((string) base64_decode(substr($value, strpos($value, ',') + 1), true));
            if (($info['mime'] ?? null) !== 'image/png') {
                $fail('Tanda tangan tidak valid. Hapus lalu tanda tangani ulang.');
            }
        }];
    }
}
