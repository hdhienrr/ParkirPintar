<?php

namespace App\Http\Requests;

use App\Enums\JenisKendaraan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SimpanAreaRequest extends FormRequest
{
    use AturanArea;

    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:100', 'unique:area_parkir,nama'],
            'jenis' => ['required', Rule::enum(JenisKendaraan::class)],
            'lantai' => ['required', 'integer', 'min:1', 'max:'.config('parkir.area.lantai_maks')],
            'jumlah_slot' => ['required', ...$this->aturanJumlahSlot()],
        ];
    }
}
