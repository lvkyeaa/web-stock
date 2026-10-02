<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Peminjaman extends Model
{
    use HasFactory;

    // 💡 Mengatasi error: Mengunci nama tabel agar Laravel tidak mencari 'peminjamen'
    protected $table = 'peminjamans';

    protected $fillable = [
        'user_id',
        'facility_request_id',
        'facility_id',
        'waktu_mulai',
        'waktu_selesai',
        'keperluan',
        'status'
    ];

    // Otomatis casting string datetime menjadi objek Carbon Laravel
    protected $casts = [
        'waktu_mulai' => 'datetime',
        'waktu_selesai' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Fasilitas yang diberikan admin (kosong sampai disetujui)
    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    // Fasilitas yang diminta customer saat mengajukan
    public function facilityRequest()
    {
        return $this->belongsTo(Facility::class, 'facility_request_id');
    }

    // Fasilitas untuk ditampilkan: yang diberikan admin jika ada, jika belum maka yang diminta
    public function displayFacility(): Facility
    {
        return $this->facility ?? $this->facilityRequest;
    }

    // Warna sesuai jenis fasilitas, gaya sesuai status — dipakai kalender & daftar agar konsisten
    public function statusStyle(): array
    {
        // Hanya garis tepi yang berwarna (warna jenis fasilitas); isi tetap putih.
        // Garis solid = disetujui, putus-putus = menunggu (lihat CSS .booking-status-*)
        $color = $this->displayFacility()->facilityType->color;

        return match ($this->status) {
            'disetujui', 'pending' => ['bg' => '#ffffff', 'border' => $color, 'text' => $color],
            default                => ['bg' => '#ffffff', 'border' => '#cbd5e1', 'text' => '#94a3b8'],
        };
    }
}
