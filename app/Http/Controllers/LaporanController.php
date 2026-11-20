<?php

namespace App\Http\Controllers;

use App\Http\Requests\FilterPendapatanRequest;
use App\Services\LayananLaporan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LaporanController extends Controller
{
    public function __construct(private readonly LayananLaporan $layanan) {}

    public function okupansi(Request $request): JsonResponse|View
    {
        $data = $this->layanan->okupansi();

        if ($request->wantsJson()) {
            return response()->json(['data' => $data]);
        }

        return view('laporan.okupansi', $data);
    }

    public function pendapatan(FilterPendapatanRequest $request): JsonResponse|View
    {
        $data = $this->layanan->pendapatan($request->dari(), $request->sampai());

        if ($request->wantsJson()) {
            return response()->json(['data' => $data]);
        }

        return view('laporan.pendapatan', $data);
    }
}
