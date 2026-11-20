<?php

namespace App\Http\Requests;

use App\Enums\StatusBooking;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterBookingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'tanggal' => ['nullable', 'date_format:Y-m-d'],
            'status' => ['nullable', Rule::enum(StatusBooking::class)],
        ];
    }

    /** Hari ini bila kosong. */
    public function tanggal(): CarbonImmutable
    {
        return $this->filled('tanggal') ? CarbonImmutable::parse($this->validated('tanggal')) : CarbonImmutable::today();
    }

    public function status(): ?StatusBooking
    {
        return $this->filled('status') ? StatusBooking::from($this->validated('status')) : null;
    }
}
