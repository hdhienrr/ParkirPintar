<?php

namespace App\Services;

use App\Models\Tarif;
use DateTimeInterface;

/**
 * Denda overstay, dihitung per blok 24 jam sejak denda_mulai:
 * jam dalam tiap blok dibulatkan ke atas (minimal 1 jam), dikali tarif per jam,
 * lalu dibatasi batas harian. Total denda = jumlah semua blok.
 */
class KalkulatorDenda
{
    private const JAM_PER_BLOK = 24;

    /** Kendaraan yang terlambat keluar sampai booking berikutnya dibatalkan sistem. */
    public function pelaku(Tarif $tarif, DateTimeInterface $mulai, DateTimeInterface $selesai): int
    {
        return $this->hitung($tarif->denda_pelaku_per_jam, $tarif->denda_pelaku_maks_harian, $mulai, $selesai);
    }

    /** Kendaraan dari booking yang dibatalkan sistem, bila tidak keluar dalam batas waktu. */
    public function korban(Tarif $tarif, DateTimeInterface $mulai, DateTimeInterface $selesai): int
    {
        return $this->hitung($tarif->denda_korban_per_jam, $tarif->denda_korban_maks_harian, $mulai, $selesai);
    }

    public function hitung(int $perJam, int $maksHarian, DateTimeInterface $mulai, DateTimeInterface $selesai): int
    {
        $detik = $selesai->getTimestamp() - $mulai->getTimestamp();

        if ($detik <= 0) {
            return 0;
        }

        $jam = intdiv($detik + 3599, 3600);
        $blokPenuh = intdiv($jam, self::JAM_PER_BLOK);
        $sisaJam = $jam % self::JAM_PER_BLOK;

        $denda = $blokPenuh * min(self::JAM_PER_BLOK * $perJam, $maksHarian);

        if ($sisaJam > 0) {
            $denda += min($sisaJam * $perJam, $maksHarian);
        }

        return $denda;
    }
}
