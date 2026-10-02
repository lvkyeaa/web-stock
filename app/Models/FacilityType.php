<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class FacilityType extends Model
{
    use HasUuids;

    protected $fillable = ['code', 'name', 'icon', 'color'];

    public function facilities()
    {
        return $this->hasMany(Facility::class);
    }

    // Label tampilan, contoh: "🚗 Mobil Dinas"
    public function label(): string
    {
        return $this->icon . ' ' . $this->name;
    }
}
