<?php

namespace App\Models;

use App\Enums\JenisKendaraan;
use Database\Factories\SlotParkirFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class SlotParkir extends Model
{
    /** @use HasFactory<SlotParkirFactory> */
    use HasFactory;

    /** Denah: 1 blok = 3 slot, 1 zona = 3 blok ke samping x 2 blok ke bawah. */
    public const SLOT_PER_BLOK = 3;

    public const BLOK_PER_ZONA = 6;

    public const SLOT_PER_ZONA = self::SLOT_PER_BLOK * self::BLOK_PER_ZONA;

    protected $table = 'slot_parkir';

    protected $fillable = [
        'area_id',
        'kode_slot',
        'jenis',
    ];

    public static function buatKode(string $jenis, int $lantai, int $nomor): string
    {
        return sprintf('%s%d-%02d', JenisKendaraan::from($jenis)->awalan(), $lantai, $nomor);
    }

    /** Nomor urut slot di lantainya (A1-07 -> 7). */
    protected function nomor(): Attribute
    {
        return Attribute::get(fn () => (int) Str::afterLast($this->kode_slot, '-'));
    }

    /** Nomor lantai dari kode slot (A1-07 -> 1). */
    public static function lantaiDariKode(string $kodeSlot): int
    {
        return (int) substr(Str::before($kodeSlot, '-'), 1);
    }

    protected function lantai(): Attribute
    {
        return Attribute::get(fn () => self::lantaiDariKode($this->kode_slot));
    }

    /**
     * Posisi slot di denah, diturunkan dari nomor urut.
     *
     * @return array{zona: int, blok: int, urutan: int}
     */
    public function posisi(): array
    {
        $indeks = $this->nomor - 1;

        return [
            'zona' => intdiv($indeks, self::SLOT_PER_ZONA),
            'blok' => intdiv($indeks % self::SLOT_PER_ZONA, self::SLOT_PER_BLOK),
            'urutan' => $indeks % self::SLOT_PER_BLOK,
        ];
    }

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
