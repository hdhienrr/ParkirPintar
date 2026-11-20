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
        $this->resource->loadMissing('slot.area');

        return [
            'id' => $this->id,
            'plat' => $this->plat,
            'jenis' => $this->jenis,
            'area' => $this->slot->area->nama,
            'kode_slot' => $this->slot->kode_slot,
            'waktu_masuk' => $this->waktu_masuk->toIso8601String(),
            'waktu_keluar' => $this->waktu_keluar?->toIso8601String(),
            'durasi_menit' => $this->durasi_menit,
            'biaya' => $this->biaya,
        ];
    }
}
