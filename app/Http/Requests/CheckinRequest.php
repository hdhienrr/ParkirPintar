<?php

namespace App\Http\Requests;

use App\Enums\JenisKendaraan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckinRequest extends FormRequest
{
    /** Contoh: B 1234 XY, D 1 A, AB 12. Spasi boleh ada atau tidak. */
    private const POLA_PLAT = '/^[A-Za-z]{1,2}\s*\d{1,4}\s*[A-Za-z]{0,3}$/';

    public function rules(): array
    {
        return [
            'slot_id' => ['required', 'integer', 'exists:slot_parkir,id'],
            'plat' => ['required', 'string', 'max:15', 'regex:'.self::POLA_PLAT],
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
