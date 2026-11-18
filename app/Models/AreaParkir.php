<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class AreaParkir extends Model
{
    protected $table = 'area_parkir';

    protected $fillable = [
        'nama',
    ];

    public function slot(): HasMany
    {
        return $this->hasMany(SlotParkir::class, 'area_id');
    }

    public function kendaraan(): HasManyThrough
    {
        return $this->hasManyThrough(KendaraanParkir::class, SlotParkir::class, 'area_id', 'slot_id');
    }
}
