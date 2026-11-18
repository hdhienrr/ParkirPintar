<?php

namespace App\Services;

use App\Models\Tarif;
use DateTimeInterface;

class KalkulatorTarif
{
    public const MENIT_GRATIS = 15;

    /**
     * @return array{durasi_menit: int, biaya: int}
     */
    public function hitung(Tarif $tarif, DateTimeInterface $masuk, DateTimeInterface $keluar): array
    {
        $detik = max(0, $keluar->getTimestamp() - $masuk->getTimestamp());
        $menit = intdiv($detik + 59, 60);

        return [
            'durasi_menit' => $menit,
            'biaya' => $this->biayaUntukMenit($tarif, $menit),
        ];
    }

    /**
     * 15 menit pertama gratis. Lewat dari itu, durasi dibulatkan ke atas per jam:
     * jam pertama memakai tarif_jam_pertama, jam berikutnya tarif_jam_berikutnya.
     * Biaya tiap 24 jam dibatasi maks_harian.
     */
    public function biayaUntukMenit(Tarif $tarif, int $menit): int
    {
        if ($menit <= self::MENIT_GRATIS) {
            return 0;
        }

        $jam = intdiv($menit + 59, 60);
        $hariPenuh = intdiv($jam, 24);
        $sisaJam = $jam % 24;

        $biaya = $hariPenuh * $tarif->maks_harian;

        if ($sisaJam > 0) {
            $biayaSisa = $tarif->tarif_jam_pertama + ($sisaJam - 1) * $tarif->tarif_jam_berikutnya;
            $biaya += min($biayaSisa, $tarif->maks_harian);
        }

        return $biaya;
    }
}
