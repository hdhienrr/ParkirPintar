<?php

namespace App\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

class FilterPendapatanRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'dari' => ['nullable', 'date_format:Y-m-d'],
            'sampai' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:dari'],
        ];
    }

    /** Hari ini bila kosong. */
    public function sampai(): CarbonImmutable
    {
        return $this->filled('sampai') ? CarbonImmutable::parse($this->validated('sampai')) : CarbonImmutable::today();
    }

    /** Rentang bawaan dihitung mundur dari tanggal sampai, termasuk hari itu sendiri. */
    public function dari(): CarbonImmutable
    {
        return $this->filled('dari')
            ? CarbonImmutable::parse($this->validated('dari'))
            : $this->sampai()->subDays(config('parkir.laporan.hari_pendapatan') - 1);
    }
}
