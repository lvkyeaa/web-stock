<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Notifications\Notifiable; // 💡 TAMBAHKAN INI
use App\Models\Riwayat;
use Illuminate\Support\Collection;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    // 💡 TAMBAHKAN Notifiable di dalam list use trait bawah ini
    use HasUuids, Notifiable, HasRoles;

    protected $table = 'users';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['name', 'username', 'email', 'password', 'team_id'];

    protected $hidden = ['password'];

    protected $casts = ['password' => 'hashed'];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    // Tim yang diketuai pegawai ini (bisa lebih dari satu)
    public function chiefOfTeams()
    {
        return $this->belongsToMany(Team::class, 'team_chief')->using(TeamChief::class)->withTimestamps();
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    // Jumlah barang (bukan kuantitas) di keranjang, untuk badge
    public function jumlahKeranjang(): int
    {
        return $this->cartItems()->count();
    }

    /**
     * Isi keranjang beserta stok tersedia tiap barang (stok - yang sudah diajukan & masih pending).
     * Keranjang tidak diubah: barang yang melebihi stok tersedia tetap ada dan ditandai (CartItem::cukup()).
     */
    public function keranjang(): Collection
    {
        return $this->cartItems()->with(['barang' => fn ($query) => $query->withDipesan()])->latest()->get();
    }

    public function riwayat()
    {
        return $this->hasMany(Riwayat::class, 'actor_id');
    }

    // Nama peran yang ditampilkan ke pengguna; kunci peran di kode/role tetap 'admin' & 'customer'
    public const LABEL_PERAN = ['admin' => 'Admin', 'customer' => 'Pengguna'];

    public function labelPeran(): string
    {
        $peran = (string) $this->getRoleNames()->first();

        return self::LABEL_PERAN[$peran] ?? ucfirst($peran);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isCustomer(): bool
    {
        return $this->hasRole('customer');
    }
}