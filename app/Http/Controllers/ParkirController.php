<?php

namespace App\Http\Controllers;

use App\Enums\JenisKendaraan;
use App\Enums\StatusBooking;
use App\Http\Requests\CheckinRequest;
use App\Http\Resources\KendaraanParkirResource;
use App\Models\Booking;
use App\Models\Tarif;
use App\Services\LayananArea;
use App\Services\LayananKarcis;
use App\Support\Format;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ParkirController extends Controller
{
    public function __construct(
        private readonly LayananKarcis $layanan,
        private readonly LayananArea $layananArea,
    ) {}

    /**
     * Halaman petugas untuk satu jenis kendaraan: denah per lantai, check-in dengan kode booking, dan check-out.
     */
    public function halaman(Request $request, string $jenis): View
    {
        $area = $this->layananArea->daftarRingkasan($jenis);

        return view('parkir', [
            'jenis' => JenisKendaraan::from($jenis),
            'area' => $area,
            'areaDipilih' => $area->firstWhere('id', (int) $request->query('area')) ?? $area->first(),
            'tarif' => Tarif::where('jenis', $jenis)->first(),
            'siapCheckin' => $this->layanan->siapCheckin($jenis),
        ]);
    }

    public function checkin(CheckinRequest $request): JsonResponse|RedirectResponse
    {
        $booking = $this->layanan->checkIn($request->validated('kode_booking'));

        if ($booking->status === StatusBooking::Menunggu) {
            return $this->jawabMenunggu($request, $booking);
        }

        if ($request->expectsJson()) {
            return (new KendaraanParkirResource($booking->kendaraan))->response()->setStatusCode(201);
        }

        return redirect()->route('karcis.show', $booking->kendaraan)->with('status', 'Check-in berhasil. Karcis parkir terbit.');
    }

    public function checkout(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $hasil = $this->layanan->checkOut($id);

        if ($request->expectsJson()) {
            return (new KendaraanParkirResource($hasil->kendaraan))->additional([
                'masuk_menyusul' => $hasil->masukMenyusul
                    ? new KendaraanParkirResource($hasil->masukMenyusul->kendaraan)
                    : null,
            ])->response();
        }

        $denda = $hasil->kendaraan->booking?->denda ?? 0;
        $pesan = "Check-out {$hasil->kendaraan->plat} berhasil. Total ".Format::rupiah($hasil->kendaraan->biaya + $denda).'.';
        if ($hasil->masukMenyusul) {
            $pesan .= " Booking {$hasil->masukMenyusul->kode_booking} yang menunggu langsung check-in.";
        }

        return back()->with('status', $pesan);
    }

    private function jawabMenunggu(Request $request, Booking $booking): JsonResponse|RedirectResponse
    {
        $batas = $booking->menunggu_sejak->copy()->addMinutes(config('parkir.booking.tunggu_penghalang_menit'));
        $pesan = "Slot {$booking->slot->kode_slot} masih ditempati kendaraan yang melewati waktu selesai. "
            .'Booking menunggu sampai pukul '.Format::jam($batas).'. Bila kendaraan itu keluar sebelumnya, check-in otomatis. '
            .'Atau pindahkan ke slot lain yang bebas, tanpa denda.';

        if ($request->expectsJson()) {
            return response()->json(['data' => [
                'status' => $booking->status->value,
                'kode_booking' => $booking->kode_booking,
                'menunggu_sampai' => $batas->toIso8601String(),
                'url_pindah' => route('admin.booking.pindah.form', $booking),
            ], 'message' => $pesan], 202);
        }

        return redirect()->route('admin.booking.pindah.form', $booking)->with('status', $pesan);
    }
}
