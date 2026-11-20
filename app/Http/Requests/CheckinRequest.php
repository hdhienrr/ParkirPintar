<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Check-in hanya dengan kode booking, bukan plat dan slot bebas.
 */
class CheckinRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'kode_booking' => ['required', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'kode_booking.required' => 'Isi atau pilih kode booking. Check-in tanpa booking tidak bisa.',
        ];
    }
}
