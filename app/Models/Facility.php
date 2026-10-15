<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Facility extends Model
{
    use HasUuids;

    protected $fillable = ['facility_type_id', 'name', 'capacity'];

    public function facilityType()
    {
        return $this->belongsTo(FacilityType::class);
    }

    public function peminjaman()
    {
        return $this->hasMany(Peminjaman::class);
    }

    // Peminjaman yang meminta fasilitas ini (facility_request_id)
    public function permintaanPeminjaman()
    {
        return $this->hasMany(Peminjaman::class, 'facility_request_id');
    }

    // Sudah dipakai peminjaman (diminta / diberikan), termasuk yang sudah dihapus (soft delete)
    public function pernahDipinjam(): bool
    {
        return $this->permintaanPeminjaman()->withTrashed()->exists() || $this->peminjaman()->withTrashed()->exists();
    }
}
