<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Notifications\Notifiable; // 💡 TAMBAHKAN INI
use App\Models\TransaksiRequest;
use App\Models\Riwayat;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    // 💡 TAMBAHKAN Notifiable di dalam list use trait bawah ini
    use HasUuids, Notifiable, HasRoles;

    protected $table = 'users';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['name', 'username', 'password'];

    protected $hidden = ['password'];

    protected $casts = ['password' => 'hashed'];

    public function transaksiRequests()
    {
        return $this->hasMany(TransaksiRequest::class, 'user_id');
    }

    public function riwayat()
    {
        return $this->hasMany(Riwayat::class, 'actor_id');
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