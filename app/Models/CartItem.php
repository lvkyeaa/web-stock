<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    use HasUuids;

    protected $fillable = ['barang_id', 'jumlah'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class);
    }

    // Jumlah di keranjang masih muat di stok tersedia (barang dimuat dengan withDipesan(), lihat User::keranjang())
    public function cukup(): bool
    {
        return $this->jumlah <= $this->barang->tersedia;
    }

    // Data satu baris halaman keranjang (data awal & respons JSON aksi keranjang)
    public function untukKeranjang(): array
    {
        return [
            'id'          => $this->id,
            'nama_barang' => $this->barang->nama_barang,
            'satuan'      => $this->barang->satuan,
            'jumlah'      => $this->jumlah,
            'tersedia'    => max(0, $this->barang->tersedia),
            'dipesan'     => (int) $this->barang->dipesan,
        ];
    }
}
