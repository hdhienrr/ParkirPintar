<?php

namespace App\Http\Requests;

use App\Enums\JenisKendaraan;
use App\Models\KendaraanParkir;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckinRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'slot_id' => ['required', 'integer', 'exists:slot_parkir,id'],
            'plat' => ['required', 'string', 'max:15', 'regex:'.KendaraanParkir::POLA_PLAT],
            'jenis' => ['required', Rule::enum(JenisKendaraan::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'plat.regex' => 'Format plat nomor tidak valid, contoh: B 1234 XY.',
        ];
    }
}
