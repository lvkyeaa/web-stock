<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Riwayat extends Model
{
    use HasUuids;

    protected $table = 'riwayat';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'order_id', 'actor_id',
        'status_sebelumnya', 'status_sesudah', 'catatan'
    ];

    protected $casts = ['created_at' => 'datetime'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}