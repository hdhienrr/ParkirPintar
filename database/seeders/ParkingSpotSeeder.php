<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ParkingSpot;

class ParkingSpotSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 1; $i <= 6; $i++) {
            ParkingSpot::updateOrCreate(
                ['spot_code' => 'A-0' . $i],
                [
                    'vehicle_type' => 'mobil',
                    'status' => 'available',
                    'hourly_rate' => 5000,
                ]
            );
        }

        for ($i = 1; $i <= 6; $i++) {
            ParkingSpot::updateOrCreate(
                ['spot_code' => 'M-0' . $i],
                [
                    'vehicle_type' => 'motor',
                    'status' => 'available',
                    'hourly_rate' => 2000,
                ]
            );
        }
    }
}