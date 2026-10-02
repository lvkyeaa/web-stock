<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Peminjaman;

class PermintaanPeminjamanBaru extends Notification
{
    use Queueable;

    protected $peminjaman;

    public function __with($peminjaman) {
        $this->peminjaman = $peminjaman;
    }

    public function __construct(Peminjaman $peminjaman)
    {
        $this->peminjaman = $peminjaman;
    }

    public function via($notifiable)
    {
        return ['database']; // Menyimpan ke database saja
    }

    public function toArray($notifiable)
    {
        return [
            'peminjaman_id' => $this->peminjaman->id,
            'pesan' => 'Permintaan baru: Peminjaman ' . $this->peminjaman->facilityRequest->facilityType->label() . ' - ' . $this->peminjaman->facilityRequest->name,
            'url' => route('peminjaman.index'), // Arahkan admin ke halaman kelola stok/peminjaman
        ];
    }
}