<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// Satu baris per file Excel yang diunggah; diproses oleh job ImportPersediaan
class ImportStatus extends Model
{
    use HasUuids;

    // Kunci status => label yang ditampilkan
    public const STATUS = [
        'start'      => 'Menunggu',
        'processing' => 'Diproses',
        'error'      => 'Gagal',
        'success'    => 'Berhasil',
    ];

    protected $fillable = ['user_id', 'nama_file', 'path', 'status', 'keterangan'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Barang yang dibuat oleh impor ini
    public function barang(): HasMany
    {
        return $this->hasMany(Barang::class);
    }
}
