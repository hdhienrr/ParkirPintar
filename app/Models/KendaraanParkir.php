<?php

namespace App\Models;

use Database\Factories\KendaraanParkirFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KendaraanParkir extends Model
{
    /** @use HasFactory<KendaraanParkirFactory> */
    use HasFactory;

    /** Contoh: B 1234 XY, D 1 A, AB 12. Spasi boleh ada atau tidak. */
    public const POLA_PLAT = '/^[A-Za-z]{1,2}\s*\d{1,4}\s*[A-Za-z]{0,3}$/';

    protected $table = 'kendaraan_parkir';

    protected $fillable = [
        'slot_id',
        'plat',
        'jenis',
        'waktu_masuk',
        'waktu_keluar',
        'biaya',
        'durasi_menit',
    ];

    protected function casts(): array
    {
        return [
            'waktu_masuk' => 'datetime',
            'waktu_keluar' => 'datetime',
            'biaya' => 'integer',
            'durasi_menit' => 'integer',
        ];
    }

    /**
     * Plat disimpan huruf besar tanpa spasi, supaya "b 1234 xy" dan "B1234XY"
     * dianggap plat yang sama oleh unique index plat_aktif.
     */
    protected function plat(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => self::normalisasiPlat($value),
        );
    }

    public static function normalisasiPlat(string $plat): string
    {
        return strtoupper(preg_replace('/\s+/', '', $plat));
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(SlotParkir::class, 'slot_id');
    }

    public function scopeAktif(Builder $query): void
    {
        $query->whereNull('waktu_keluar');
    }
}
