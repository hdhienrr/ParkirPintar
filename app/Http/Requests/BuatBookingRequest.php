<?php

namespace App\Http\Requests;

use App\Models\KendaraanParkir;
use Illuminate\Foundation\Http\FormRequest;

class BuatBookingRequest extends FormRequest
{
    use AturanJadwal;

    public function rules(): array
    {
        return [
            'plat' => ['required', 'string', 'max:15', 'regex:'.KendaraanParkir::POLA_PLAT],
            ...$this->aturanJadwal(),
            'slot_id' => ['required', 'integer', 'exists:slot_parkir,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'plat.required' => 'Plat nomor wajib diisi.',
            'plat.regex' => 'Format plat nomor tidak valid, contoh: B 1234 XY.',
            'slot_id.required' => 'Pilih slot di denah terlebih dahulu.',
            ...$this->pesanJadwal(),
        ];
    }
}
