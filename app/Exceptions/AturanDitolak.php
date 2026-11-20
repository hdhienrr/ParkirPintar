<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Permintaan yang melanggar aturan bisnis. Tidak mengubah data,
 * dan pesannya ditampilkan ke pengguna apa adanya.
 */
abstract class AturanDitolak extends RuntimeException
{
    public function __construct(string $pesan, public readonly int $status = 409)
    {
        parent::__construct($pesan);
    }

    /** Nama kolom error saat dikembalikan ke formulir. */
    abstract protected function kunciError(): string;

    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $this->getMessage()], $this->status);
        }

        return back()->withInput()->withErrors([$this->kunciError() => $this->getMessage()]);
    }

    /**
     * Pesan ini untuk pengguna, bukan untuk log (bisa memuat plat nomor).
     */
    public function report(): bool
    {
        return true;
    }
}
