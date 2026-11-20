<?php

namespace App\Support;

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

    public static function persen(int $bagian, int $total): int
    {
        return $total > 0 ? (int) round($bagian / $total * 100) : 0;
    }
}
