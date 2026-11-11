<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Keputusan satu tingkat persetujuan pengajuan (level ketua / admin)
class OrderApproval extends Model
{
    use HasUuids;

    protected $fillable = ['level', 'user_id', 'keputusan', 'signature_path', 'catatan'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // Yang memutuskan (ketua tim / admin)
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
