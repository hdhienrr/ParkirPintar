<?php

namespace App\Services;

use App\Exceptions\ParkirDitolak;
use App\Models\KendaraanParkir;
use App\Models\SlotParkir;
use App\Models\Tarif;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class LayananParkir
{
    public function __construct(private readonly KalkulatorTarif $kalkulator) {}

    /**
     * @throws ParkirDitolak
     */
    public function checkIn(int $slotId, string $plat, string $jenis): KendaraanParkir
    {
        $plat = KendaraanParkir::normalisasiPlat($plat);

        try {
            return DB::transaction(function () use ($slotId, $plat, $jenis) {
                // Lock baris slot supaya dua check-in ke slot yang sama diproses bergantian.
                $slot = SlotParkir::with('area')->lockForUpdate()->findOrFail($slotId);

                if ($slot->jenis !== $jenis) {
                    throw ParkirDitolak::jenisTidakSesuai($slot->kode_slot, $slot->jenis);
                }

                if (KendaraanParkir::aktif()->where('plat', $plat)->exists()) {
                    throw ParkirDitolak::platMasihDiDalam($plat);
                }

                if ($slot->kendaraanAktif()->exists()) {
                    $masihAdaKosong = SlotParkir::where('area_id', $slot->area_id)
                        ->where('jenis', $jenis)
                        ->whereDoesntHave('kendaraanAktif')
                        ->exists();

                    throw $masihAdaKosong
                        ? ParkirDitolak::slotTerisi($slot->kode_slot)
                        : ParkirDitolak::areaPenuh($slot->area->nama);
                }

                return KendaraanParkir::create([
                    'slot_id' => $slot->id,
                    'plat' => $plat,
                    'jenis' => $jenis,
                    'waktu_masuk' => now()->startOfSecond(),
                ]);
            });
        } catch (UniqueConstraintViolationException) {
            // Pengaman terakhir: unique index plat_aktif / slot_aktif menolak
            // request bersamaan yang lolos pengecekan di atas.
            if (KendaraanParkir::aktif()->where('plat', $plat)->exists()) {
                throw ParkirDitolak::platMasihDiDalam($plat);
            }

            throw ParkirDitolak::slotTerisi(SlotParkir::findOrFail($slotId)->kode_slot);
        }
    }

    /**
     * @throws ParkirDitolak
     */
    public function checkOut(int $kendaraanId): KendaraanParkir
    {
        return DB::transaction(function () use ($kendaraanId) {
            $kendaraan = KendaraanParkir::lockForUpdate()->findOrFail($kendaraanId);

            if ($kendaraan->waktu_keluar !== null) {
                throw ParkirDitolak::sudahCheckout();
            }

            $tarif = Tarif::where('jenis', $kendaraan->jenis)->firstOrFail();
            $keluar = now()->startOfSecond();

            $kendaraan->update(
                ['waktu_keluar' => $keluar] + $this->kalkulator->hitung($tarif, $kendaraan->waktu_masuk, $keluar)
            );

            return $kendaraan;
        });
    }
}
