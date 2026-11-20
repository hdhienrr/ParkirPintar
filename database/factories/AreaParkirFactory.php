<?php

namespace Database\Factories;

use App\Models\AreaParkir;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AreaParkir>
 */
class AreaParkirFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nama' => 'Area '.fake()->unique()->numberBetween(100, 999),
        ];
    }
}
