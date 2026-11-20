<?php

namespace App\Http\Requests;

use App\Enums\JenisKendaraan;
use Carbon\CarbonImmutable;
use Illuminate\Validation\Rule;

/**
 * Isian jadwal booking: jenis kendaraan, tanggal, jam mulai (jam bulat), dan durasi.
 * Aturan jam operasional, durasi, dan batas hari diperiksa di LayananBooking.
 */
trait AturanJadwal
{
    protected function aturanJadwal(): array
    {
        return [
            'jenis' => ['required', Rule::enum(JenisKendaraan::class)],
            'tanggal' => ['required', 'date_format:Y-m-d'],
            'jam' => ['required', 'integer', 'between:0,23'],
            'durasi' => ['required', 'integer', 'min:1'],
        ];
    }

    protected function pesanJadwal(): array
    {
        return [
            'jam.integer' => 'Jam mulai harus jam bulat, misalnya 8 untuk 08.00.',
            'tanggal.date_format' => 'Format tanggal tidak valid.',
        ];
    }

    public function waktuMulai(): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('Y-m-d', $this->validated('tanggal'))
            ->setTime((int) $this->validated('jam'), 0);
    }

    public function durasiJam(): int
    {
        return (int) $this->validated('durasi');
    }
}
