<?php

namespace App\Http\Controllers;

use App\Enums\StatusBooking;
use App\Http\Requests\FilterBookingRequest;
use App\Http\Requests\PindahBookingRequest;
use App\Models\Booking;
use App\Services\LayananBooking;
use App\Services\LayananKarcis;
use App\Services\LayananPemindahan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman dan aksi admin atas booking: daftar semua booking, pemindahan slot, dan tandai keluar.
 */
class AdminBookingController extends Controller
{
    public function __construct(
        private readonly LayananBooking $layananBooking,
        private readonly LayananKarcis $layananKarcis,
        private readonly LayananPemindahan $layananPemindahan,
    ) {}

    public function index(FilterBookingRequest $request): View
    {
        return view('admin.booking', [
            'booking' => $this->layananBooking->padaTanggal($request->tanggal(), $request->status()),
            'tanggal' => $request->tanggal(),
            'statusDipilih' => $request->status(),
            'semuaStatus' => StatusBooking::cases(),
        ]);
    }

    public function formPindah(Booking $booking): View
    {
        $booking = $this->layananBooking->detail($booking);

        return view('admin.pindah', [
            'booking' => $booking,
            'slotPengganti' => $this->layananPemindahan->slotPengganti($booking),
        ]);
    }

    public function pindah(PindahBookingRequest $request, Booking $booking): JsonResponse|RedirectResponse
    {
        $booking = $this->layananPemindahan->pindah($booking, (int) $request->validated('slot_id'));

        if ($request->expectsJson()) {
            return response()->json(['data' => [
                'kode_booking' => $booking->kode_booking,
                'status' => $booking->status->value,
                'kode_slot' => $booking->slot->kode_slot,
                'nomor_karcis' => $booking->kendaraan?->nomor_karcis,
            ]]);
        }

        if ($booking->kendaraan) {
            return redirect()->route('karcis.show', $booking->kendaraan)
                ->with('status', "Booking dipindah ke {$booking->slot->kode_slot} dan langsung check-in. Karcis parkir terbit.");
        }

        return redirect()->route('admin.booking.index', ['tanggal' => $booking->waktu_mulai->toDateString()])
            ->with('status', "Booking {$booking->kode_booking} dipindah ke slot {$booking->slot->kode_slot}.");
    }

    /** Tandai keluar: kendaraan dari booking yang dibatalkan sistem sudah meninggalkan area. */
    public function keluar(Request $request, Booking $booking): JsonResponse|RedirectResponse
    {
        $booking = $this->layananKarcis->tandaiKeluar($booking);

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
