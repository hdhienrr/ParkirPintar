<?php

namespace App\Http\Controllers;

use App\Enums\JenisKendaraan;
use App\Http\Requests\BuatBookingRequest;
use App\Http\Requests\KetersediaanRequest;
use App\Models\Booking;
use App\Services\LayananBooking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function __construct(private readonly LayananBooking $layanan) {}

    public function create(): View
    {
        $aturan = config('parkir.booking');
        $terdekat = $this->layanan->jadwalTerdekat();

        return view('booking.baru', [
            'jenisKendaraan' => JenisKendaraan::cases(),
            'aturan' => $aturan,
            'tanggalAwal' => $terdekat->toDateString(),
            'jamAwal' => $terdekat->hour,
            'tanggalMin' => today()->toDateString(),
            'tanggalMaks' => today()->addDays($aturan['hari_ke_depan_maks'])->toDateString(),
        ]);
    }

    /**
     * Denah slot yang masih bebas untuk jadwal yang dipilih. Tanpa plat, jadi aman sebagai GET.
     */
    public function ketersediaan(KetersediaanRequest $request): JsonResponse
    {
        $area = $this->layanan->ketersediaan($request->validated('jenis'), $request->waktuMulai(), $request->durasiJam());

        return response()->json(['data' => $area]);
    }

    public function store(BuatBookingRequest $request): JsonResponse|RedirectResponse
    {
        $booking = $this->layanan->buat(
            $request->user(),
            $request->validated('plat'),
            $request->validated('jenis'),
            (int) $request->validated('slot_id'),
            $request->waktuMulai(),
            $request->durasiJam(),
        );

        if ($request->expectsJson()) {
            return response()->json(['data' => ['kode_booking' => $booking->kode_booking]], 201);
        }

        return redirect()->route('booking.show', $booking)->with('status', 'Booking berhasil dibuat.');
    }

    public function index(Request $request): View
    {
        return view('booking.saya', ['booking' => $this->layanan->milik($request->user())]);
    }

    public function show(Booking $booking): View
    {
        return view('booking.bukti', ['booking' => $this->layanan->detail($booking)]);
    }

    public function batal(Booking $booking): RedirectResponse
    {
        $this->layanan->batal($booking);

        return redirect()->route('booking.index')->with('status', "Booking {$booking->kode_booking} dibatalkan.");
    }
}
