<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Notifications\Notification;

// Notifikasi lonceng (tabel notifications) untuk pengajuan persediaan; isi sama dengan pesan WhatsApp-nya.
// Bentuk data (pesan, url) sama dengan notifikasi peminjaman, jadi satu lonceng bisa menampilkan keduanya.
class KabarPengajuanPersediaan extends Notification
{
    public function __construct(private Order $order, private string $event, private string $pesan, private string $url)
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'event'    => $this->event,
            'pesan'    => $this->pesan,
            'url'      => $this->url,
        ];
    }
}
