<?php

namespace Database\Factories;

use App\Models\KendaraanParkir;
use App\Models\SlotParkir;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Kendaraan yang masih di dalam (belum check-out).
 *
 * @extends Factory<KendaraanParkir>
 */
class KendaraanParkirFactory extends Factory
{
    public function definition(): array
    {
        return [
            'slot_id' => SlotParkir::factory(),
            'plat' => fake()->unique()->bothify('B #### ??'),
            'jenis' => fn (array $atribut) => SlotParkir::findOrFail($atribut['slot_id'])->jenis,
            'waktu_masuk' => now()->startOfSecond(),
        ];
    }
}
