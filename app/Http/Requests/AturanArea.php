<?php

namespace App\Http\Requests;

use App\Models\SlotParkir;

/**
 * Aturan validasi yang sama untuk membuat dan mengubah area.
 */
trait AturanArea
{
    /** @return list<string> */
    protected function aturanJumlahSlot(): array
    {
        return [
            'integer',
            'min:'.SlotParkir::SLOT_PER_ZONA,
            'max:'.config('parkir.area.slot_maks'),
            'multiple_of:'.SlotParkir::SLOT_PER_ZONA,
        ];
    }

    public function messages(): array
    {
        return [
            'nama.unique' => 'Nama area sudah dipakai.',
            'jumlah_slot.multiple_of' => 'Jumlah slot harus kelipatan '.SlotParkir::SLOT_PER_ZONA.' (satu zona).',
        ];
    }
}
