<?php

namespace App\Http\Controllers;

use App\Enums\JenisKendaraan;
use App\Http\Requests\CheckinRequest;
use App\Http\Resources\KendaraanParkirResource;
use App\Models\Tarif;
use App\Services\LayananArea;
use App\Services\LayananParkir;
use App\Support\Format;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ParkirController extends Controller
{
    public function __construct(
        private readonly LayananParkir $layanan,
        private readonly LayananArea $layananArea,
    ) {}

    /**
     * Halaman check-in dan check-out untuk satu jenis kendaraan, dengan tab lantai dan denah slot.
     */
    public function halaman(Request $request, string $jenis): View
    {
        $area = $this->layananArea->daftarRingkasan($jenis);

        return view('parkir', [
            'jenis' => JenisKendaraan::from($jenis),
            'area' => $area,
            'areaDipilih' => $area->firstWhere('id', (int) $request->query('area')) ?? $area->first(),
            'tarif' => Tarif::where('jenis', $jenis)->first(),
        ]);
    }

    public function checkin(CheckinRequest $request): JsonResponse|RedirectResponse
    {
        $data = $request->validated();
        $kendaraan = $this->layanan->checkIn((int) $data['slot_id'], $data['plat'], $data['jenis']);

        if ($request->expectsJson()) {
            return (new KendaraanParkirResource($kendaraan))->response();
        }

        return back()->with('status', "Check-in {$kendaraan->plat} di slot {$kendaraan->slot->kode_slot} berhasil.");
    }

    public function checkout(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $kendaraan = $this->layanan->checkOut($id);

        if ($request->expectsJson()) {
            return (new KendaraanParkirResource($kendaraan))->response();
        }

        return back()->with('status', "Check-out {$kendaraan->plat} berhasil. Biaya ".Format::rupiah($kendaraan->biaya).'.');
    }
}
