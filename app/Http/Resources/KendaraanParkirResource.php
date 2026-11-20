<?php

namespace App\Http\Resources;

use App\Models\KendaraanParkir;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin KendaraanParkir
 */
class KendaraanParkirResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing('slot.area', 'booking');
        $denda = $this->booking?->denda ?? 0;

        return [
            'id' => $this->id,
            'nomor_karcis' => $this->nomor_karcis,
            'kode_booking' => $this->booking?->kode_booking,
            'plat' => $this->plat,
            'jenis' => $this->jenis,
            'area' => $this->slot->area->nama,
            'kode_slot' => $this->slot->kode_slot,
            'waktu_masuk' => $this->waktu_masuk->toIso8601String(),
            'waktu_keluar' => $this->waktu_keluar?->toIso8601String(),
            'durasi_menit' => $this->durasi_menit,
            'biaya' => $this->biaya,
            'denda' => $denda,
            'total' => $this->biaya === null ? null : $this->biaya + $denda,
            'url_karcis' => $this->nomor_karcis ? route('karcis.show', $this->resource) : null,
        ];
    }
}
