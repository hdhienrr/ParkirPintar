<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SlotParkir extends Model
{
    protected $table = 'slot_parkir';

    protected $fillable = [
        'area_id',
        'kode_slot',
        'jenis',
    ];

    public function area(): BelongsTo
    {
        return $this->belongsTo(AreaParkir::class, 'area_id');
    }

    public function kendaraan(): HasMany
    {
        return $this->hasMany(KendaraanParkir::class, 'slot_id');
    }

    public function kendaraanAktif(): HasOne
    {
        return $this->hasOne(KendaraanParkir::class, 'slot_id')->whereNull('waktu_keluar');
    }
}
