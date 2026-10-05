<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// stock_id, nama_barang & satuan adalah salinan saat checkout; barang bisa null jika barangnya sudah dihapus
class OrderItem extends Model
{
    use HasUuids;

    protected $fillable = ['barang_id', 'stock_id', 'nama_barang', 'satuan', 'jumlah'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class);
    }
}
