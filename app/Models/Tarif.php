<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tarif extends Model
{
    protected $table = 'tarif';

    protected $fillable = [
        'jenis',
        'tarif_jam_pertama',
        'tarif_jam_berikutnya',
        'maks_harian',
    ];

    protected function casts(): array
    {
        return [
            'tarif_jam_pertama' => 'integer',
            'tarif_jam_berikutnya' => 'integer',
            'maks_harian' => 'integer',
        ];
    }
}
