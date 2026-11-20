<?php

namespace App\Http\Controllers;

use App\Enums\JenisKendaraan;
use App\Http\Requests\SimpanAreaRequest;
use App\Http\Requests\UbahAreaRequest;
use App\Models\AreaParkir;
use App\Services\LayananArea;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AreaController extends Controller
{
    public function __construct(private readonly LayananArea $layanan) {}

    public function index(Request $request): JsonResponse|View
    {
        $area = $this->layanan->daftarRingkasan();

        if ($request->wantsJson()) {
            return response()->json(['data' => $area]);
        }

        return view('areas.index', [
            'area' => $area,
            'diubah' => $area->firstWhere('id', (int) $request->query('ubah')),
        ]);
    }

    public function store(SimpanAreaRequest $request): JsonResponse|RedirectResponse
    {
        $data = $request->validated();
        $area = $this->layanan->buat($data['nama'], $data['jenis'], (int) $data['lantai'], (int) $data['jumlah_slot']);

        if ($request->wantsJson()) {
            return response()->json(['data' => $this->layanan->ringkasan($area)], 201);
        }

        return redirect()->route('areas.index')->with('status', "Area {$area->nama} dibuat dengan {$data['jumlah_slot']} slot.");
    }

    public function update(UbahAreaRequest $request, AreaParkir $area): JsonResponse|RedirectResponse
    {
        $data = $request->validated();
        $this->layanan->ubah($area, $data['nama'], isset($data['jumlah_slot']) ? (int) $data['jumlah_slot'] : null);

        if ($request->wantsJson()) {
            return response()->json(['data' => $this->layanan->ringkasan($area)]);
        }

        return redirect()->route('areas.index')->with('status', "Area {$area->nama} diperbarui.");
    }

    public function destroy(Request $request, AreaParkir $area): JsonResponse|RedirectResponse
    {
        $this->layanan->hapus($area);

        if ($request->wantsJson()) {
            return response()->json(null, 204);
        }

        return redirect()->route('areas.index')->with('status', "Area {$area->nama} dihapus.");
    }

    /**
     * Denah slot dalam JSON. Dibuka langsung di browser, diarahkan ke halaman parkir lantai itu.
     */
    public function slots(Request $request, AreaParkir $area): JsonResponse|RedirectResponse
    {
        if (! $request->wantsJson()) {
            $jenis = $this->layanan->ringkasan($area)['jenis'] ?? JenisKendaraan::Mobil->value;

            return redirect()->route('parkir', ['jenis' => $jenis, 'area' => $area->id]);
        }

        return response()->json(['data' => $this->layanan->denah($area)]);
    }
}
