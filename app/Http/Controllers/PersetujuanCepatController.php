<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use App\Services\PersetujuanPengajuan;
use Illuminate\Http\Request;

// Persetujuan cepat tanpa login, lewat tautan bertanda tangan (signed URL) yang hanya dikirim di pesan WhatsApp.
// Tautan berisi pengajuan + penerima; keputusan dicatat atas nama penerima itu. Hanya berlaku selama pengajuan
// masih menunggu keputusan penerima (ketua tim yang dipilih / admin), jadi otomatis mati setelah diproses.
// GET hanya menampilkan halaman (pratinjau tautan WhatsApp tidak boleh ikut menyetujui); keputusan lewat POST.
class PersetujuanCepatController extends Controller
{
    public function show(Order $order, User $user)
    {
        $order->load(['user', 'team', 'personResponsible', 'items']);

        return view('persetujuan-cepat.index', [
            'order'       => $order,
            'user'        => $user,
            'tingkat'     => $this->tingkat($order, $user),
            'statusLabel' => OrderController::STATUS[$order->status] ?? $order->status,
            'keterangan'  => $this->keterangan($order),
        ]);
    }

    public function store(Request $request, Order $order, User $user, PersetujuanPengajuan $persetujuan)
    {
        // Penerima lain (mis. admin lain) sudah memutuskan: keputusan itu tidak boleh ditimpa
        $tingkat = $this->tingkat($order, $user);
        if (! $tingkat) {
            return redirect()->to($request->fullUrl())->with('error', $this->keterangan($order) ?? 'Pengajuan ini tidak lagi menunggu keputusan Anda.');
        }

        $request->validate([
            'status'       => 'required|in:disetujui,ditolak',
            'alasan'       => 'required_if:status,ditolak|nullable|string|max:500', // alasan wajib saat menolak, sama dengan dialog Tolak
            'tanda_tangan' => $tingkat === 'ketua' ? ['exclude_unless:status,disetujui', ...OrderController::aturanTandaTangan()] : 'exclude',
        ], [
            'tanda_tangan.required' => 'Tanda tangan dulu sebelum menyetujui.',
            'alasan.required_if'    => 'Isi alasan penolakan terlebih dahulu.',
        ]);

        $error = $tingkat === 'ketua'
            ? $persetujuan->ketua($order, $user, $request->status, $request->alasan, $request->tanda_tangan)
            : $persetujuan->admin($order, $user, $request->status, $request->alasan);

        // Kalah balapan dengan penerima lain yang memutuskan bersamaan (dicek ulang di dalam transaksi berkunci): sebutkan siapa
        if ($error && ! $this->tingkat($order->fresh(), $user)) {
            $error = $this->keterangan($order->fresh()) ?? $error;
        }

        $pesan = $error ?? ($request->status === 'disetujui' ? 'Pengajuan berhasil disetujui.' : 'Pengajuan telah ditolak.');

        return redirect()->to($request->fullUrl())->with($error ? 'error' : 'success', $pesan);
    }

    // Siapa yang terakhir memutuskan pengajuan ini dan kapan, mis. "Pengajuan ini sudah disetujui oleh admin Budi pada 20 November 2026, 10:15."
    private function keterangan(Order $order): ?string
    {
        if ($order->status === 'dibatalkan') {
            return 'Pengajuan ini sudah dibatalkan.';
        }

        // Keputusan admin selalu sesudah keputusan ketua tim (created_at bisa sama dalam detik yang sama)
        $approvals = $order->approvals()->with('user:id,name')->get();
        $terakhir = $approvals->firstWhere('level', 'admin') ?? $approvals->firstWhere('level', 'ketua');
        if (! $terakhir) {
            return null;
        }

        $oleh = ($terakhir->level === 'ketua' ? 'ketua tim ' : 'admin ') . $terakhir->user->name;

        return "Pengajuan ini sudah {$terakhir->keputusan} oleh {$oleh} pada {$terakhir->created_at->tanggalJam()}.";
    }

    // Tingkat keputusan yang boleh diambil penerima tautan saat ini; null jika tidak ada (sudah diproses / bukan wewenangnya)
    private function tingkat(Order $order, User $user): ?string
    {
        return match (true) {
            $order->status === 'menunggu_ketua' && $order->person_responsible_user_id === $user->id => 'ketua',
            $order->status === 'menunggu_admin' && $user->hasRole('admin')                          => 'admin',
            default                                                                                 => null,
        };
    }
}
