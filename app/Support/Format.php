<?php

namespace App\Support;

use Carbon\CarbonInterface;

class Format
{
    public static function rupiah(?int $nilai): string
    {
        return $nilai === null ? '-' : 'Rp'.number_format($nilai, 0, ',', '.');
    }

    /** 150 -> "2 jam 30 menit", 60 -> "1 jam", 45 -> "45 menit". */
    public static function durasi(int $menit): string
    {
        $jam = intdiv($menit, 60);
        $sisa = $menit % 60;

        return trim(($jam > 0 ? "{$jam} jam " : '').($sisa > 0 || $jam === 0 ? "{$sisa} menit" : ''));
    }

    /** Senin, 23 November 2026 */
    public static function tanggal(CarbonInterface $waktu): string
    {
        return $waktu->locale('id')->translatedFormat('l, d F Y');
    }

    /** 08.00 */
    public static function jam(CarbonInterface $waktu): string
    {
        return $waktu->format('H.i');
    }

    /** 8 -> 08.00 */
    public static function jamBulat(int $jam): string
    {
        return sprintf('%02d.00', $jam);
    }

    public static function persen(int $bagian, int $total): int
    {
        return $total > 0 ? (int) round($bagian / $total * 100) : 0;
    }
}
