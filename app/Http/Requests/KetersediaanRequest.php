<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class KetersediaanRequest extends FormRequest
{
    use AturanJadwal;

    public function rules(): array
    {
        return $this->aturanJadwal();
    }

    public function messages(): array
    {
        return $this->pesanJadwal();
    }
}
