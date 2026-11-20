<?php

namespace App\Jobs;

use App\Models\WhatsappMessage;
use App\Services\WhatsApp;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\RequestException;
use Throwable;

// Kirim satu pesan WhatsApp dari log whatsapp_messages, di queue. Dicoba 3 kali; hasil tiap percobaan dicatat di log.
class KirimWhatsApp implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [60, 300]; // jeda sebelum percobaan ke-2 dan ke-3 (detik)

    public function __construct(public WhatsappMessage $pesan)
    {
    }

    public function handle(WhatsApp $whatsApp): void
    {
        $this->pesan->increment('attempts');

        try {
            $response = $whatsApp->kirim($this->pesan->phone, $this->pesan->message);
        } catch (Throwable $e) {
            $this->pesan->update([
                'response_code' => $e instanceof RequestException ? $e->response->status() : null,
                'error'         => $e instanceof RequestException ? $e->response->body() : $e->getMessage(),
            ]);

            throw $e; // queue mencoba ulang; setelah percobaan terakhir failed() menandai gagal
        }

        $this->pesan->update([
            'status'        => 'terkirim',
            'response_code' => $response->status(),
            'error'         => null,
            'sent_at'       => now(),
        ]);
    }

    public function failed(?Throwable $e): void
    {
        $this->pesan->update(['status' => 'gagal']);
    }
}
