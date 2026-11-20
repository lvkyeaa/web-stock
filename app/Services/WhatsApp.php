<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

// Kirim pesan WhatsApp lewat API Majapahit (push-pesan). Siapa menerima apa: KabarPengajuan; pengiriman lewat job KirimWhatsApp.
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
}
