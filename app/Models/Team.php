<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    use HasUuids;

    protected $fillable = ['name', 'short_name'];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    // Ketua tim (lewat tabel team_chief)
    public function chiefs()
    {
        return $this->belongsToMany(User::class, 'team_chief')->using(TeamChief::class)->withTimestamps();
    }
}
