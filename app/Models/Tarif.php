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
        'denda_pelaku_per_jam',
        'denda_pelaku_maks_harian',
        'denda_korban_per_jam',
        'denda_korban_maks_harian',
    ];

    protected function casts(): array
    {
        return [
            'tarif_jam_pertama' => 'integer',
            'tarif_jam_berikutnya' => 'integer',
            'maks_harian' => 'integer',
            'denda_pelaku_per_jam' => 'integer',
            'denda_pelaku_maks_harian' => 'integer',
            'denda_korban_per_jam' => 'integer',
            'denda_korban_maks_harian' => 'integer',
        ];
    }
}
