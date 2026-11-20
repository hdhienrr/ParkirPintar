<?php

namespace App\Exceptions;

/**
 * Booking atau pembatalan yang melanggar aturan booking.
 */
class BookingDitolak extends AturanDitolak
{
    public static function jamTidakBulat(): self
    {
        return new self('Booking harus dimulai tepat di awal jam, misalnya 08.00.', 422);
    }

    public static function durasiTidakValid(int $min, int $maks): self
    {
        return new self("Durasi booking harus {$min} sampai {$maks} jam.", 422);
    }

    public static function waktuSudahLewat(): self
    {
        return new self('Waktu mulai booking harus di masa depan.', 422);
    }

    public static function terlaluJauh(int $hari): self
    {
        return new self("Booking paling jauh {$hari} hari ke depan.", 422);
    }

    public static function diLuarJamOperasional(int $buka, int $tutup): self
    {
        return new self(sprintf('Booking harus di dalam jam operasional %02d.00 sampai %02d.00.', $buka, $tutup), 422);
    }

    public static function jenisTidakSesuai(string $kodeSlot, string $jenisSlot): self
    {
        return new self("Slot {$kodeSlot} hanya untuk {$jenisSlot}.", 422);
    }

    public static function akunMasihPunyaBooking(): self
    {
        return new self('Anda masih punya booking aktif. Booking baru bisa dibuat setelah booking itu selesai atau dibatalkan.');
    }

    public static function platMencapaiBatas(string $plat, int $maks): self
    {
        return new self("Plat {$plat} sudah ada di {$maks} booking aktif. Tunggu salah satunya selesai.");
    }

    public static function slotTerbooking(string $kodeSlot): self
    {
        return new self("Slot {$kodeSlot} sudah dibooking pada jam itu atau terlalu dekat dengan booking lain. Pilih slot lain.");
    }

    public static function semuaSlotPenuh(string $jenis): self
    {
        return new self("Semua slot {$jenis} sudah dibooking pada jam itu. Pilih jam lain.");
    }

    public static function tidakBisaDibatalkan(): self
    {
        return new self('Booking hanya bisa dibatalkan sebelum waktu mulai, selama belum check-in.');
    }

    protected function kunciError(): string
    {
        return 'booking';
    }
}
