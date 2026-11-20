<?php

namespace App\Services;

use App\Jobs\KirimWhatsApp;
use App\Models\Order;
use App\Models\User;
use App\Models\WhatsappMessage;
use App\Notifications\KabarPengajuanPersediaan;
use Illuminate\Support\Facades\URL;

// Kabar pengajuan persediaan ke pihak terkait: notifikasi lonceng (tabel notifications) + pesan WhatsApp (log whatsapp_messages, dikirim lewat queue).
class KabarPengajuan
{
    // Panggil SETELAH perubahan pengajuan tersimpan (di luar transaksi).
    // $event: diajukan | disetujui_ketua | ditolak_ketua | keputusan_admin
    public function kirim(Order $order, string $event, ?string $alasan = null): void
    {
        $order->loadMissing(['user', 'personResponsible', 'items']);
        $pemohon = $order->user;
        $ketua = $order->personResponsible;
        $alasan = filled($alasan) ? "\nAlasan: {$alasan}" : '';
        $linkPemohon = route($pemohon->hasRole('admin') ? 'pengajuan.saya' : 'pengajuan.index');
        // Tautan persetujuan cepat tanpa login, khusus untuk satu penerima; hanya dibuat di pesan WhatsApp
        $linkCepat = fn (User $penerima) => URL::signedRoute('persetujuan-cepat.show', ['order' => $order, 'user' => $penerima]);
        // Akhir pesan WhatsApp: tautan di barisnya sendiri agar bisa diklik
        $persetujuanCepat = fn (User $penerima) => "\n\nSetujui atau tolak di sini:\n{$linkCepat($penerima)}";
        $buka = "\nBuka:\n{$linkPemohon}";

        // [penerima, isi, akhir pesan WhatsApp, tautan di lonceng (penerima sudah login)]
        $kabar = match ($event) {
            'diajukan' => [
                [$ketua, "Pengajuan {$order->code} dari {$pemohon->name} ({$order->items->count()} barang) menunggu persetujuan Anda.", $persetujuanCepat($ketua), route('pengajuan.ketua')],
            ],
            'disetujui_ketua' => [
                // Pemohon yang juga admin cukup menerima kabar sebagai pemohon
                ...User::role('admin')->whereKeyNot($pemohon->id)->get()->map(fn (User $admin) => [
                    $admin, "Pengajuan {$order->code} dari {$pemohon->name} telah disetujui ketua tim {$ketua->name} dan menunggu persetujuan Anda.",
                    $persetujuanCepat($admin), route('pengajuan.index', ['status' => 'menunggu_admin']),
                ]),
                [$pemohon, "Pengajuan {$order->code} Anda telah disetujui ketua tim {$ketua->name} dan menunggu persetujuan admin.", $buka, $linkPemohon],
            ],
            'ditolak_ketua' => [
                [$pemohon, "Pengajuan {$order->code} Anda ditolak oleh ketua tim {$ketua->name}.{$alasan}", $buka, $linkPemohon],
            ],
            'keputusan_admin' => [
                [$pemohon, $order->status === 'disetujui'
                    ? "Pengajuan {$order->code} Anda telah disetujui admin."
                    : "Pengajuan {$order->code} Anda ditolak oleh admin.{$alasan}", $buka, $linkPemohon],
            ],
        };

        // Kepala pesan WhatsApp: nama aplikasi (tebal di WhatsApp) + emoji sesuai kejadian
        $emoji = match (true) {
            $event === 'diajukan'                                         => '📝',
            $event === 'disetujui_ketua', $order->status === 'disetujui' => '✅',
            default                                                       => '❌',
        };
        $kepala = "*[Wirabhumi - Aplikasi Layanan Fasilitas dan Persediaan]* {$emoji}\n\n";

        foreach ($kabar as [$penerima, $isi, $akhirWhatsApp, $url]) {
            if (! $penerima) {
                continue;
            }

            $penerima->notify(new KabarPengajuanPersediaan($order, $event, $isi, $url));

            $log = WhatsappMessage::create([
                'order_id' => $order->id,
                'user_id'  => $penerima->id,
                'phone'    => $penerima->phone,
                'event'    => $event,
                'message'  => $kepala . $isi . $akhirWhatsApp,
                'status'   => $penerima->phone ? 'menunggu' : 'dilewati',
            ]);

            if ($log->status === 'menunggu') {
                KirimWhatsApp::dispatch($log);
            }
        }
    }
}
