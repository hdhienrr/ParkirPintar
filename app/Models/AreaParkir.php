<?php

namespace App\Models;

use Database\Factories\AreaParkirFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class AreaParkir extends Model
{
    /** @use HasFactory<AreaParkirFactory> */
    use HasFactory;

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

    /**
     * Tambahkan kolom jenis, lantai, total_slot, dan terisi.
     * Satu area = satu lantai untuk satu jenis kendaraan, jadi jenis dan lantai
     * diambil dari slot pertamanya.
     */
    public function scopeDenganRingkasan(Builder $query): void
    {
        $slotPertama = fn (string $kolom) => SlotParkir::select($kolom)
            ->whereColumn('area_id', 'area_parkir.id')
            ->orderBy('id')
            ->limit(1);

        $query->addSelect([
            'jenis' => $slotPertama('jenis'),
            'kode_slot_pertama' => $slotPertama('kode_slot'),
        ])->withCount([
            'slot as total_slot',
            'kendaraan as terisi' => fn (Builder $q) => $q->whereNull('kendaraan_parkir.waktu_keluar'),
        ]);
    }

    public function scopeJenis(Builder $query, string $jenis): void
    {
        $query->whereHas('slot', fn (Builder $q) => $q->where('jenis', $jenis));
    }

    /**
     * Ringkasan untuk JSON dan halaman. Butuh scope denganRingkasan.
     */
    public function ringkasan(): array
    {
        $total = (int) $this->total_slot;
        $terisi = (int) $this->terisi;

        return [
            'id' => $this->id,
            'nama' => $this->nama,
            'jenis' => $this->jenis,
            'lantai' => $this->kode_slot_pertama ? SlotParkir::lantaiDariKode($this->kode_slot_pertama) : null,
            'total_slot' => $total,
            'terisi' => $terisi,
            'kosong' => $total - $terisi,
        ];
    }
}
