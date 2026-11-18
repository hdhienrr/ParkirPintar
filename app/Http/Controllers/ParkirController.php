<?php

namespace App\Http\Controllers;

use App\Models\KendaraanParkir;
use App\Services\LayananParkir;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ParkirController extends Controller
{
    public function __construct(private readonly LayananParkir $layanan) {}

    public function checkin(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'slot_id' => ['required', 'integer', 'exists:slot_parkir,id'],
            'plat' => ['required', 'string', 'max:15', 'regex:/^[A-Za-z]{1,2}\s*\d{1,4}\s*[A-Za-z]{0,3}$/'],
            'jenis' => ['required', Rule::in(['mobil', 'motor'])],
        ], [
            'plat.regex' => 'Format plat nomor tidak valid, contoh: B 1234 XY.',
        ]);

        $kendaraan = $this->layanan->checkIn((int) $data['slot_id'], $data['plat'], $data['jenis']);

        if ($request->expectsJson()) {
            return response()->json(['data' => $this->ringkas($kendaraan)], 201);
        }

        return back()->with('status', "Check-in {$kendaraan->plat} di slot {$kendaraan->slot->kode_slot} berhasil.");
    }

    public function checkout(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $kendaraan = $this->layanan->checkOut($id);

        if ($request->expectsJson()) {
            return response()->json(['data' => $this->ringkas($kendaraan)]);
        }

        return back()->with('status', "Check-out {$kendaraan->plat} berhasil. Biaya Rp".number_format($kendaraan->biaya, 0, ',', '.').'.');
    }

    private function ringkas(KendaraanParkir $kendaraan): array
    {
        $kendaraan->loadMissing('slot.area');

        return [
            'id' => $kendaraan->id,
            'plat' => $kendaraan->plat,
            'jenis' => $kendaraan->jenis,
            'area' => $kendaraan->slot->area->nama,
            'kode_slot' => $kendaraan->slot->kode_slot,
            'waktu_masuk' => $kendaraan->waktu_masuk->toIso8601String(),
            'waktu_keluar' => $kendaraan->waktu_keluar?->toIso8601String(),
            'durasi_menit' => $kendaraan->durasi_menit,
            'biaya' => $kendaraan->biaya,
        ];
    }
}
