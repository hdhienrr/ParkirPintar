<?php

namespace Database\Factories;

use App\Enums\JenisKendaraan;
use App\Models\AreaParkir;
use App\Models\SlotParkir;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SlotParkir>
 */
class SlotParkirFactory extends Factory
{
    /** Lantai yang tidak dipakai seeder, supaya kode slot tidak bentrok. */
    private const LANTAI_UJI = 9;

    public function definition(): array
    {
        return [
            'area_id' => AreaParkir::factory(),
            'jenis' => JenisKendaraan::Mobil->value,
            'kode_slot' => fn (array $atribut) => SlotParkir::buatKode(
                $atribut['jenis'],
                self::LANTAI_UJI,
                fake()->unique()->numberBetween(1, 99),
            ),
        ];
    }

    public function motor(): static
    {
        return $this->state(['jenis' => JenisKendaraan::Motor->value]);
    }
}
