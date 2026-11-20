<?php

namespace App\Exceptions;

/**
 * Check-in, check-out, atau perubahan area yang melanggar aturan bisnis.
 */
class ParkirDitolak extends AturanDitolak
{
    public static function jenisTidakSesuai(string $kodeSlot, string $jenisSlot): self
    {
        return new self("Slot {$kodeSlot} hanya untuk {$jenisSlot}.", 422);
    }

    public static function platMasihDiDalam(string $plat): self
    {
        return new self("Kendaraan {$plat} masih berada di dalam area parkir.");
    }

    public static function slotTerisi(string $kodeSlot): self
    {
        return new self("Slot {$kodeSlot} sudah terisi. Pilih slot lain.");
    }

    public static function areaPenuh(string $namaArea): self
    {
        return new self("{$namaArea} sudah penuh.");
    }

    public static function sudahCheckout(): self
    {
        return new self('Kendaraan ini sudah check-out.');
    }

    protected function kunciError(): string
    {
        return 'parkir';
    }
}
