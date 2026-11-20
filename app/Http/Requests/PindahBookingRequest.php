<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PindahBookingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'slot_id' => ['required', 'integer', 'exists:slot_parkir,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'slot_id.required' => 'Pilih slot pengganti.',
        ];
    }
}
