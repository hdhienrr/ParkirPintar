<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UbahAreaRequest extends FormRequest
{
    use AturanArea;

    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:100', Rule::unique('area_parkir', 'nama')->ignore($this->route('area'))],
            'jumlah_slot' => ['sometimes', ...$this->aturanJumlahSlot()],
        ];
    }
}
