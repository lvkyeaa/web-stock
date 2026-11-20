<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Log satu pesan WhatsApp ke satu penerima: menunggu → terkirim / gagal; dilewati jika penerima tidak punya nomor HP
class WhatsappMessage extends Model
{
    use HasUuids;

    protected $fillable = ['order_id', 'user_id', 'phone', 'event', 'message', 'status', 'attempts', 'response_code', 'error', 'sent_at'];

    protected $casts = ['sent_at' => 'datetime'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // Penerima
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
