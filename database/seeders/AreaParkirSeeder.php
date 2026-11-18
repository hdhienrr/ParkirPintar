<?php

namespace Database\Seeders;

use App\Models\AreaParkir;
use App\Models\SlotParkir;
use Illuminate\Database\Seeder;

class AreaParkirSeeder extends Seeder
{
    /**
     * Satu lantai = 4 zona x 6 blok x 3 slot.
     */
    public const SLOT_PER_LANTAI = 72;

    /**
     * jenis => [awalan kode slot, jumlah lantai]
     */
    private const AREA = [
        'mobil' => ['A', 3],
        'motor' => ['M', 2],
    ];

    public function run(): void
    {
        $sekarang = now();

        foreach (self::AREA as $jenis => [$awalan, $jumlahLantai]) {
            for ($lantai = 1; $lantai <= $jumlahLantai; $lantai++) {
                $area = AreaParkir::firstOrCreate(['nama' => ucfirst($jenis).' Lantai '.$lantai]);

                $slot = [];
                for ($nomor = 1; $nomor <= self::SLOT_PER_LANTAI; $nomor++) {
                    $slot[] = [
                        'area_id' => $area->id,
                        'kode_slot' => sprintf('%s%d-%02d', $awalan, $lantai, $nomor),
                        'jenis' => $jenis,
                        'created_at' => $sekarang,
                        'updated_at' => $sekarang,
                    ];
                }

                // Aman dijalankan ulang: slot yang sudah ada tidak diduplikasi.
                SlotParkir::upsert($slot, ['area_id', 'kode_slot'], ['jenis', 'updated_at']);
            }
        }
    }
}
