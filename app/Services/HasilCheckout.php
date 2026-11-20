<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\KendaraanParkir;

/**
 * Hasil check-out: kendaraan yang keluar, dan booking yang sedang menunggu slot itu
 * bila langsung di-check-in setelah slotnya kosong.
 */
final readonly class HasilCheckout
{
    public function __construct(
        public KendaraanParkir $kendaraan,
        public ?Booking $masukMenyusul = null,
    ) {}
}
