<?php

namespace App\Http\Controllers;

use App\Models\Facility;
use App\Models\FacilityType;
use App\Models\Peminjaman;
use App\Models\User;
use App\Notifications\PermintaanPeminjamanBaru;
use App\Notifications\StatusPeminjamanDiperbarui;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

// Controller peminjaman fasilitas (mobil & ruang) untuk role admin maupun customer
class BookingController extends Controller
{
    public function index(Request $request)
    {
        $user = User::find(Auth::id());

        $isGuest = !$user; // tamu: hanya melihat kalender & daftar, tanpa aksi
        $isAdmin = $user?->hasRole('admin') ?? false;

        // Hanya kerangka halaman; data peminjaman diambil lewat data() (JSON)
        $types = FacilityType::orderBy('name')->get();

        // Daftar fasilitas per kode jenis untuk dropdown form
        $facilities = Facility::with('facilityType')->orderBy('name')->get()
            ->groupBy(fn($facility) => $facility->facilityType->code)
            ->map(fn($group) => $group->map->only(['id', 'name'])->values());

        // Kondisi awal tampilan dari query string (agar posisi tetap saat reload / dari tautan notifikasi)
        $initial = [
            'tab'    => $request->view === 'list' ? 'list' : 'calendar',
            'type'   => $types->contains('code', $request->type) ? $request->type : '',
            'status' => in_array($request->status, ['pending', 'disetujui', 'ditolak']) ? $request->status : '',
            'q'      => mb_substr(trim((string) $request->q), 0, 100),
            'mine'   => !$isGuest && $request->boolean('mine'),
            'mode'   => $request->mode === 'month' ? 'month' : 'week',
            'date'   => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $request->date) ? $request->date : null,
        ];

        return view('peminjaman.index', [
            'facilities' => $facilities,
            'types'      => $types,
            'initial'    => $initial,
            'isAdmin'    => $isAdmin,
            'isGuest'    => $isGuest,
        ]);
    }

    // API JSON: peminjaman yang beririsan dengan periode start–end, sesuai filter jenis & status
    public function data(Request $request)
    {
        $request->validate([
            'start'  => 'required|date',
            'end'    => 'required|date|after:start',
            'type'   => 'nullable|string',
            'status' => 'nullable|in:pending,disetujui,ditolak',
            'q'      => 'nullable|string|max:100',
            'mine'   => 'nullable|boolean',
        ]);

        $user = User::find(Auth::id());
        $isGuest = !$user; // dipanggil dari halaman publik /monitoring
        $isAdmin = $user?->hasRole('admin') ?? false;

        // Periode dari kalender membawa offset zona waktu browser; samakan dengan zona waktu aplikasi
        $start = Carbon::parse($request->start)->setTimezone(config('app.timezone'));
        $end = Carbon::parse($request->end)->setTimezone(config('app.timezone'));

        $bookings = Peminjaman::with(['user', 'facility.facilityType', 'facilityRequest.facilityType'])
            // Jenis fasilitas yang diberikan selalu sama dengan yang diminta, jadi filter lewat permintaan
            ->when($request->type, fn($query) => $query->whereRelation('facilityRequest.facilityType', 'code', $request->type))
            ->when($request->status, fn($query) => $query->where('status', $request->status))
            // Peminjaman saya: hanya milik pengguna yang login (diabaikan untuk tamu)
            ->when($user && $request->boolean('mine'), fn($query) => $query->where('user_id', $user->id))
            // Pencarian: keperluan, nama / username pemohon, nama fasilitas (diminta maupun diberikan)
            ->when(trim((string) $request->q) !== '', function ($query) use ($request, $isGuest) {
                $kata = '%' . trim($request->q) . '%';
                $query->where(function ($q) use ($kata, $isGuest) {
                    $q->where('keperluan', 'like', $kata)
                        // Tamu hanya bisa mencari nama pemohon (username = email, tidak boleh bisa ditebak dari halaman publik)
                        ->orWhereHas('user', fn($u) => $u->where('name', 'like', $kata)->when(!$isGuest, fn($w) => $w->orWhere('username', 'like', $kata)))
                        ->orWhereHas('facilityRequest', fn($f) => $f->where('name', 'like', $kata))
                        ->orWhereHas('facility', fn($f) => $f->where('name', 'like', $kata));
                });
            })
            ->where('waktu_mulai', '<', $end)
            ->where('waktu_selesai', '>', $start)
            ->orderBy('waktu_mulai')
            ->get()
            ->map(fn($item) => [
                'id'          => $item->id,
                'facility'    => $item->displayFacility()->name, // diberikan jika ada, jika belum yang diminta
                'requested_facility' => $item->facilityRequest->only(['id', 'name']),
                'assigned_facility'  => $item->facility?->only(['id', 'name']),
                'type'        => $item->facilityRequest->facilityType->only(['code', 'name', 'icon', 'color']) + ['label' => $item->facilityRequest->facilityType->label()],
                'user'        => $item->user->name ?? 'Tidak Diketahui',
                'start'       => $item->waktu_mulai->toIso8601String(),
                'end'         => $item->waktu_selesai->toIso8601String(),
                'start_label' => $item->waktu_mulai->tanggalJam(),
                'end_label'   => $item->waktu_selesai->tanggalJam(),
                'keperluan'   => $item->keperluan,
                'status'      => $item->status,
                'style'       => $item->statusStyle(), // warna sama untuk item kalender & badge daftar
                'status_url'  => $isAdmin ? route('peminjaman.update-status', $item->id) : null,
                'can_edit'    => !$isGuest && $this->bisaDiedit($item), // peminjaman milik sendiri yang menunggu / ditolak
                'can_delete'  => $isAdmin || (!$isGuest && $this->bisaDiedit($item)), // admin: semua; customer: sama dengan aturan edit
                'delete_url'  => $isGuest ? null : route('peminjaman.destroy', $item->id),
                // Nilai awal form edit (format input datetime-local)
                'edit'        => [
                    'url'         => $isGuest ? null : route('peminjaman.update', $item->id),
                    'type'        => $item->facilityRequest->facilityType->code,
                    'facility_request_id' => $item->facility_request_id,
                    'waktu_mulai' => $item->waktu_mulai->format('Y-m-d\TH:i'),
                    'waktu_selesai' => $item->waktu_selesai->format('Y-m-d\TH:i'),
                    'keperluan'   => $item->keperluan,
                ],
            ]);

        return response()->json(['data' => $bookings]);
    }

    public function store(Request $request)
    {
        $this->validasiPeminjaman($request);

        $konflik = $this->bentrokDengan($request->facility_request_id, $request->waktu_mulai, $request->waktu_selesai);
        if ($konflik->isNotEmpty()) {
            return $this->gagalBentrok($request, $konflik);
        }

        // Peminjaman yang dibuat admin langsung disetujui, milik customer menunggu persetujuan
        $user = User::find(Auth::id());
        $isAdmin = $user->hasRole('admin');

        try {
            $peminjamanBaru = Peminjaman::create([
                'user_id' => $user->id,
                'facility_request_id' => $request->facility_request_id,
                'facility_id' => $isAdmin ? $request->facility_request_id : null,
                'waktu_mulai' => $request->waktu_mulai,
                'waktu_selesai' => $request->waktu_selesai,
                'keperluan' => $request->keperluan,
                'status' => $isAdmin ? 'disetujui' : 'pending',
            ]);

            // PICU NOTIFIKASI KE ADMIN (hanya untuk pengajuan customer)
            if (!$isAdmin) {
                foreach (User::role('admin')->get() as $admin) {
                    $admin->notify(new PermintaanPeminjamanBaru($peminjamanBaru));
                }
            }

            return $this->berhasil($request, $isAdmin ? 'Peminjaman berhasil dibuat dan disetujui!' : 'Pengajuan peminjaman berhasil dikirim!');
        } catch (\Exception $e) {
            // Detail galat hanya dicatat di log, tidak ditampilkan ke pengguna
            report($e);
            $pesan = 'Terjadi kesalahan saat menyimpan peminjaman. Silakan coba lagi.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $pesan], 500);
            }

            return redirect()->back()->withInput()->with('error', $pesan);
        }
    }

    // Hanya dapat diakses lewat route admin (middleware role:admin)
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status'      => 'required|in:disetujui,ditolak',
            'facility_id' => 'nullable|exists:facilities,id',
        ]);

        $peminjaman = Peminjaman::with('facilityRequest')->findOrFail($id);

        // Keputusan boleh diubah kapan saja (menunggu / disetujui / ditolak).
        // Disetujui: fasilitas yang diberikan (default = yang sudah diberikan, lalu yang diminta), harus sejenis dan tidak bentrok
        // Ditolak: tidak ada fasilitas yang diberikan
        $facilityId = null;
        if ($request->status === 'disetujui') {
            $facilityId = $request->facility_id ?: ($peminjaman->facility_id ?: $peminjaman->facility_request_id);

            $sejenis = Facility::whereKey($facilityId)
                ->where('facility_type_id', $peminjaman->facilityRequest->facility_type_id)
                ->exists();
            if (!$sejenis) {
                return $this->gagalUbahStatus($request, 'Fasilitas yang diberikan harus sejenis dengan fasilitas yang diminta.');
            }

            // Bentrok: pesan & daftar peminjaman yang bentrok ditampilkan di dialog persetujuan (di bawah pilihan fasilitas)
            $konflik = $this->bentrokDengan($facilityId, $peminjaman->waktu_mulai, $peminjaman->waktu_selesai, $peminjaman->id);
            if ($konflik->isNotEmpty()) {
                return $this->gagalBentrok($request, $konflik, 'facility_id');
            }
        }

        // Tidak ada perubahan (status & fasilitas sama): tidak perlu menyimpan / mengirim notifikasi lagi
        if ($peminjaman->status === $request->status && $peminjaman->facility_id === $facilityId) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Tidak ada perubahan pada peminjaman ini.'])
                : redirect()->back()->with('success', 'Tidak ada perubahan pada peminjaman ini.');
        }

        $peminjaman->update([
            'status' => $request->status,
            'facility_id' => $facilityId,
        ]);

        // KIRIM NOTIFIKASI KE CUSTOMER YANG MENGAJUKAN
        $customer = User::find($peminjaman->user_id);
        if ($customer) {
            $customer->notify(new StatusPeminjamanDiperbarui($peminjaman));
        }

        // Dari dialog kalender (fetch) cukup kembalikan JSON tanpa reload halaman
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Status peminjaman berhasil diperbarui!']);
        }

        return redirect()->back()->with('success', 'Status peminjaman berhasil diperbarui!');
    }

    // Pengguna mengubah peminjaman miliknya (menunggu / ditolak): fasilitas yang diminta, waktu, keperluan.
    // Customer: kembali menunggu persetujuan. Admin: langsung disetujui (sama seperti saat membuat).
    public function update(Request $request, $id)
    {
        $peminjaman = Peminjaman::findOrFail($id);

        abort_unless($this->bisaDiedit($peminjaman), 403, 'Peminjaman ini tidak dapat diubah.');

        $this->validasiPeminjaman($request);

        $konflik = $this->bentrokDengan($request->facility_request_id, $request->waktu_mulai, $request->waktu_selesai);
        if ($konflik->isNotEmpty()) {
            return $this->gagalBentrok($request, $konflik);
        }

        $isAdmin = User::find(Auth::id())->hasRole('admin');

        $peminjaman->update([
            'facility_request_id' => $request->facility_request_id,
            'facility_id' => $isAdmin ? $request->facility_request_id : null, // customer: ditentukan ulang oleh admin saat menyetujui
            'waktu_mulai' => $request->waktu_mulai,
            'waktu_selesai' => $request->waktu_selesai,
            'keperluan' => $request->keperluan,
            'status' => $isAdmin ? 'disetujui' : 'pending',
        ]);

        if ($isAdmin) {
            return $this->berhasil($request, 'Perubahan peminjaman berhasil disimpan dan disetujui.');
        }

        // Pengajuan customer yang diubah perlu ditinjau ulang oleh admin
        foreach (User::role('admin')->get() as $admin) {
            $admin->notify(new PermintaanPeminjamanBaru($peminjaman));
        }

        return $this->berhasil($request, 'Perubahan peminjaman berhasil dikirim dan menunggu persetujuan.');
    }

    // Hapus peminjaman. Admin: semua peminjaman. Customer: milik sendiri yang menunggu / ditolak.
    public function destroy(Request $request, $id)
    {
        $peminjaman = Peminjaman::findOrFail($id);

        $isAdmin = User::find(Auth::id())->hasRole('admin');
        abort_unless($isAdmin || $this->bisaDiedit($peminjaman), 403, 'Peminjaman ini tidak dapat dihapus.');

        $peminjaman->delete();

        return $this->berhasil($request, 'Peminjaman berhasil dihapus.');
    }

    // Hanya pemilik, dan hanya saat status menunggu / ditolak
    private function bisaDiedit(Peminjaman $peminjaman): bool
    {
        return $peminjaman->user_id === Auth::id() && in_array($peminjaman->status, ['pending', 'ditolak']);
    }

    private function validasiPeminjaman(Request $request): void
    {
        // Tanggal mulai tidak boleh sebelum hari ini menurut server (jam diabaikan; zona waktu aplikasi: config app.timezone)
        $request->validate([
            'facility_request_id' => 'required|exists:facilities,id',
            'waktu_mulai' => 'required|date|after_or_equal:today',
            'waktu_selesai' => [
                'required', 'date', 'after:waktu_mulai',
                // Peminjaman tidak boleh melewati hari (selesai di tanggal yang sama dengan mulai)
                function ($attribute, $value, $fail) use ($request) {
                    if (strtotime((string) $request->waktu_mulai) === false || strtotime((string) $value) === false) {
                        return; // format tanggal tidak valid sudah ditangani aturan 'date'
                    }
                    if (Carbon::parse($value)->toDateString() !== Carbon::parse($request->waktu_mulai)->toDateString()) {
                        $fail('Peminjaman harus selesai pada hari yang sama dengan waktu mulai.');
                    }
                },
            ],
            'keperluan' => 'required|string',
        ], [
            'keperluan.required' => 'Keperluan wajib diisi.',
            'waktu_mulai.after_or_equal' => 'Tanggal mulai tidak boleh sebelum hari ini.',
        ]);
    }

    // Cek Bentrok Jadwal dengan peminjaman yang sudah disetujui pada fasilitas yang diberikan.
    // Waktu selesai tidak termasuk (sama seperti kalender): 09:00–10:00 dan 10:00–11:00 berurutan, tidak bentrok.
    // Bentrok jika masing-masing mulai sebelum yang lain selesai (tumpang tindih minimal 1 menit).
    private function bentrokDengan(string $facilityId, $mulai, $selesai, ?int $kecualiId = null): Collection
    {
        return Peminjaman::with(['user', 'facility'])
            ->where('facility_id', $facilityId)
            ->where('status', 'disetujui')
            ->when($kecualiId, fn($query) => $query->whereKeyNot($kecualiId))
            ->where('waktu_mulai', '<', $selesai)
            ->where('waktu_selesai', '>', $mulai)
            ->orderBy('waktu_mulai')
            ->get();
    }

    // Bentrok ditampilkan di form / dialog persetujuan (di bawah pilihan fasilitas $field), bukan sebagai pesan di halaman
    private function gagalBentrok(Request $request, Collection $konflik, string $field = 'facility_request_id')
    {
        $pesan = 'Fasilitas ini sudah dipakai peminjaman lain yang disetujui pada waktu tersebut. Pilih waktu atau fasilitas lain.';

        if ($request->expectsJson()) {
            return response()->json([
                'message'   => $pesan,
                'errors'    => [$field => [$pesan]],
                'conflicts' => $konflik->map(fn($item) => [
                    'id'          => $item->id,
                    'facility'    => $item->facility->name,
                    'user'        => $item->user->name ?? 'Tidak Diketahui',
                    'start_label' => $item->waktu_mulai->tanggalJam(),
                    'end_label'   => $item->waktu_selesai->tanggalJam(),
                    'keperluan'   => $item->keperluan,
                ])->values(),
            ], 422);
        }

        return redirect()->back()->withInput()->withErrors([$field => $pesan]);
    }

    private function berhasil(Request $request, string $pesan)
    {
        return $request->expectsJson()
            ? response()->json(['message' => $pesan])
            : redirect()->back()->with('success', $pesan);
    }

    private function gagalUbahStatus(Request $request, string $pesan)
    {
        return $request->expectsJson()
            ? response()->json(['message' => $pesan], 422)
            : redirect()->back()->with('error', $pesan);
    }
}
