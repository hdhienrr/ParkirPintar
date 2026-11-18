<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Permintaan check-in/check-out yang melanggar aturan bisnis.
 * Tidak mengubah data, dan ditampilkan ke pengguna apa adanya.
 */
class ParkirDitolak extends RuntimeException
{
    public function __construct(string $pesan, public readonly int $status = 409)
    {
        parent::__construct($pesan);
    }

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

    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $this->getMessage()], $this->status);
        }

        return back()->withInput()->withErrors(['parkir' => $this->getMessage()]);
    }

    /**
     * Pesan ini untuk pengguna, bukan untuk log (memuat plat nomor).
     */
    public function report(): bool
    {
        return true;
    }
}
