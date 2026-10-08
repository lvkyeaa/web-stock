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

    protected $fillable = ['code', 'status', 'alasan', 'team_id', 'person_responsible_user_id'];

    public static function buatKode(): string
    {
        return 'REQ-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Tim yang dipilih saat checkout
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    // Ketua tim/penanggung jawab yang dipilih saat checkout
    public function personResponsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'person_responsible_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function riwayat(): HasMany
    {
        return $this->hasMany(Riwayat::class);
    }

    // Format: {urutan pengajuan disetujui di bulan itu}/{nama singkat tim}/{bulan romawi}/{tahun}, contoh 1/Umum/VIII/2026
    // Beri nomor urut surat saat pengajuan DISETUJUI. Panggil di dalam transaksi persetujuan.
    // Nomor = urutan pengajuan disetujui dalam bulan pengajuan dibuat; disimpan, jadi tidak berubah setelah dicetak.
    public function beriNomor(): void
    {
        if ($this->nomor_urut) {
            return;
        }

        $periode = $this->created_at->format('Y-m');

        // Kunci nomor-nomor bulan ini: persetujuan bersamaan menunggu di sini, jadi tidak ada nomor ganda
        // (unique(periode_nomor, nomor_urut) di database menjadi pengaman terakhir)
        $terakhir = self::where('periode_nomor', $periode)->lockForUpdate()->max('nomor_urut');

        $this->forceFill(['periode_nomor' => $periode, 'nomor_urut' => (int) $terakhir + 1])->save();
    }

    // Format: {nomor urut}/{nama singkat tim}/{bulan romawi}/{tahun}, contoh 1/Umum/VIII/2026
    // Pengajuan lama tanpa tim: '-' di posisi tim
    public function nomorSurat(): string
    {
        $bulanRomawi = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'][$this->created_at->month - 1];

        return sprintf('%d/%s/%s/%d', $this->nomor_urut, $this->team->short_name ?? '-', $bulanRomawi, $this->created_at->year);
    }
}
