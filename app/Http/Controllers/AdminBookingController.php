<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Services\LayananKarcis;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Aksi admin atas booking. Daftar semua booking dan pemindahan slot menyusul di langkah 5.
 */
class AdminBookingController extends Controller
{
    public function __construct(private readonly LayananKarcis $layanan) {}

    /** Tandai keluar: kendaraan dari booking yang dibatalkan sistem sudah meninggalkan area. */
    public function keluar(Request $request, Booking $booking): JsonResponse|RedirectResponse
    {
        $booking = $this->layanan->tandaiKeluar($booking);

        if ($request->expectsJson()) {
            return response()->json(['data' => [
                'kode_booking' => $booking->kode_booking,
                'waktu_keluar' => $booking->waktu_keluar->toIso8601String(),
                'denda' => $booking->denda,
            ]]);
        }

        return redirect()->route('booking.show', $booking)->with('status', 'Kendaraan tercatat keluar. Struk keluar terbit.');
    }
}
