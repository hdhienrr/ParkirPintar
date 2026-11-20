<?php

namespace App\Enums;

enum JenisKendaraan: string
{
    case Mobil = 'mobil';
    case Motor = 'motor';

    /** Awalan kode slot, misalnya A1-01 (mobil lantai 1) dan M2-72 (motor lantai 2). */
    public function awalan(): string
    {
        return match ($this) {
            self::Mobil => 'A',
            self::Motor => 'M',
        };
    }

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /** Gambar kendaraan dilihat dari atas, untuk slot terisi di denah. */
    public function gambar(): string
    {
        return match ($this) {
            self::Mobil => 'images/car-top.png',
            self::Motor => 'images/motor-top.png',
        };
    }

    /** @return list<string> */
    public static function nilai(): array
    {
        return array_column(self::cases(), 'value');
    }
}
