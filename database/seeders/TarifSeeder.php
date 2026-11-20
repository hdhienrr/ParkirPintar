<?php

namespace Database\Seeders;

use App\Models\Tarif;
use Illuminate\Database\Seeder;

class TarifSeeder extends Seeder
{
    public function run(): void
    {
        $tarif = [
            'motor' => [
                'tarif_jam_pertama' => 2000,
                'tarif_jam_berikutnya' => 1000,
                'maks_harian' => 20000,
                'denda_pelaku_per_jam' => 10000,
                'denda_pelaku_maks_harian' => 50000,
                'denda_korban_per_jam' => 5000,
                'denda_korban_maks_harian' => 25000,
            ],
            'mobil' => [
                'tarif_jam_pertama' => 5000,
                'tarif_jam_berikutnya' => 3000,
                'maks_harian' => 50000,
                'denda_pelaku_per_jam' => 20000,
                'denda_pelaku_maks_harian' => 100000,
                'denda_korban_per_jam' => 10000,
                'denda_korban_maks_harian' => 50000,
            ],
        ];

        foreach ($tarif as $jenis => $nilai) {
            Tarif::updateOrCreate(['jenis' => $jenis], $nilai);
        }
    }
}
