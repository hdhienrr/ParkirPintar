<?php

namespace App\Enums;

enum StatusBooking: string
{
    case Dipesan = 'dipesan';
    case Menunggu = 'menunggu';
    case Aktif = 'aktif';
    case Selesai = 'selesai';
    case Dibatalkan = 'dibatalkan';
    case Hangus = 'hangus';
    case DibatalkanSistem = 'dibatalkan_sistem';

    /**
     * Status yang masih memegang slot dan dihitung sebagai booking aktif milik akun dan plat.
     *
     * @return list<self>
     */
    public static function berlaku(): array
    {
        return [self::Dipesan, self::Menunggu, self::Aktif];
    }

    public function masihBerlaku(): bool
    {
        return in_array($this, self::berlaku(), true);
    }

    public function label(): string
    {
        return match ($this) {
            self::Dipesan => 'Dipesan',
            self::Menunggu => 'Menunggu slot',
            self::Aktif => 'Sedang parkir',
            self::Selesai => 'Selesai',
            self::Dibatalkan => 'Dibatalkan',
            self::Hangus => 'Hangus',
            self::DibatalkanSistem => 'Dibatalkan sistem',
        };
    }
}
