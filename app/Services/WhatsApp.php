<?php

namespace App\Services;

use App\Jobs\KirimWhatsApp;
use App\Models\Order;
use App\Models\User;
use App\Models\WhatsappMessage;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;

// Pesan WhatsApp lewat API Majapahit (push-pesan). Pesan pengajuan dicatat di whatsapp_messages lalu dikirim lewat queue.
class WhatsApp
{
    // Kirim satu pesan; melempar exception jika gagal / bukan 2xx (job lalu mencoba ulang)
    public function kirim(string $nomor, string $pesan): Response
    {
        return Http::withHeaders([
            'EXTERNAL-API-KEY' => config('services.whatsapp.key'),
            'Referer'          => 'majapahit',
            'Pragma'           => 'no-cache',
        ])
            ->timeout(30)
            ->post(config('services.whatsapp.url'), [
                'id_whatsapp' => "{$nomor}@c.us",
                'pesan'       => $pesan,
            ])
            ->throw();
    }

    // Kabari pihak terkait setelah perubahan pengajuan tersimpan. Panggil SETELAH transaksi selesai.
    // $event: diajukan | disetujui_ketua | ditolak_ketua | keputusan_admin
    public function kabariPengajuan(Order $order, string $event, ?string $alasan = null): void
    {
        $order->loadMissing(['user', 'personResponsible', 'items']);
        $pemohon = $order->user;
        $ketua = $order->personResponsible;
        $alasan = filled($alasan) ? "\nAlasan: {$alasan}" : '';
        $linkPemohon = route($pemohon->hasRole('admin') ? 'pengajuan.saya' : 'pengajuan.index');
        // Tautan persetujuan cepat tanpa login, khusus untuk satu penerima; hanya dibuat di pesan WhatsApp ini
        $linkCepat = fn (User $penerima) => URL::signedRoute('persetujuan-cepat.show', ['order' => $order, 'user' => $penerima]);

        $pesan = match ($event) {
            'diajukan' => [
                [$ketua, "Pengajuan {$order->code} dari {$pemohon->name} ({$order->items->count()} barang) menunggu persetujuan Anda.\n\nSetujui atau tolak di sini:\n{$linkCepat($ketua)}"],
            ],
            'disetujui_ketua' => [
                // Pemohon yang juga admin cukup menerima pesan sebagai pemohon
                ...User::role('admin')->whereKeyNot($pemohon->id)->get()->map(fn (User $admin) => [
                    $admin, "Pengajuan {$order->code} dari {$pemohon->name} telah disetujui ketua tim {$ketua->name} dan menunggu persetujuan Anda.\n\nSetujui atau tolak di sini:\n{$linkCepat($admin)}",
                ]),
                [$pemohon, "Pengajuan {$order->code} Anda telah disetujui ketua tim {$ketua->name} dan menunggu persetujuan admin.\nBuka:\n{$linkPemohon}"],
            ],
            'ditolak_ketua' => [
                [$pemohon, "Pengajuan {$order->code} Anda ditolak oleh ketua tim {$ketua->name}.{$alasan}\nBuka:\n{$linkPemohon}"],
            ],
            'keputusan_admin' => [
                [$pemohon, $order->status === 'disetujui'
                    ? "Pengajuan {$order->code} Anda telah disetujui admin.\nBuka:\n{$linkPemohon}"
                    : "Pengajuan {$order->code} Anda ditolak oleh admin.{$alasan}\nBuka:\n{$linkPemohon}"],
            ],
        };

        // Kepala pesan: nama aplikasi (tebal di WhatsApp) + emoji sesuai kejadian
        $emoji = match (true) {
            $event === 'diajukan'                                         => '📝',
            $event === 'disetujui_ketua', $order->status === 'disetujui' => '✅',
            default                                                       => '❌',
        };
        $kepala = "*[Wirabhumi - Aplikasi Layanan Fasilitas dan Persediaan]* {$emoji}\n\n";

        foreach ($pesan as [$penerima, $isi]) {
            $isi = $kepala . $isi;

            $log = WhatsappMessage::create([
                'order_id' => $order->id,
                'user_id'  => $penerima?->id,
                'phone'    => $penerima?->phone,
                'event'    => $event,
                'message'  => $isi,
                'status'   => $penerima?->phone ? 'menunggu' : 'dilewati',
            ]);

            if ($log->status === 'menunggu') {
                KirimWhatsApp::dispatch($log);
            }
        }
    }
}
