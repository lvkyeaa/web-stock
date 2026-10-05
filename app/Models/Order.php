<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

// Pengajuan barang: pending → disetujui (stok dipotong) / ditolak
class Order extends Model
{
    use HasUuids;

    protected $fillable = ['code', 'status', 'alasan'];

    public static function buatKode(): string
    {
        return 'REQ-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function riwayat(): HasMany
    {
        return $this->hasMany(Riwayat::class);
    }

    // Format: {urutan pengajuan disetujui di bulan itu}/{username}/{bulan romawi}/{tahun}, contoh 1/umum/VIII/2026
    public function nomorSurat(): string
    {
        $nomorUrut = self::where('status', 'disetujui')
            ->whereYear('created_at', $this->created_at->year)
            ->whereMonth('created_at', $this->created_at->month)
            ->where('created_at', '<', $this->created_at)
            ->count() + 1;

        $bulanRomawi = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'][$this->created_at->month - 1];

        return sprintf('%d/%s/%s/%d', $nomorUrut, strtolower($this->user->username ?? 'umum'), $bulanRomawi, $this->created_at->year);
    }
}
