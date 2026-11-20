<?php

namespace App\Support;

class Format
{
    public static function rupiah(?int $nilai): string
    {
        return $nilai === null ? '-' : 'Rp'.number_format($nilai, 0, ',', '.');
    }

    public static function persen(int $bagian, int $total): int
    {
        return $total > 0 ? (int) round($bagian / $total * 100) : 0;
    }
}
