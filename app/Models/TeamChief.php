<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\Pivot;

class TeamChief extends Pivot
{
    use HasUuids;

    protected $table = 'team_chief';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['team_id', 'user_id'];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
