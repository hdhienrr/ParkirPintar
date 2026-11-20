<?php

namespace App\Http\Controllers;

use App\Models\AreaParkir;
use App\Models\KendaraanParkir;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LaporanController extends Controller
{
    public function okupansi(Request $request): JsonResponse|View
    {
        $area = AreaParkir::denganRingkasan()->orderBy('nama')->get()->map->ringkasan();

        $jumlahkan = fn ($baris) => [
            'total_slot' => $baris->sum('total_slot'),
            'terisi' => $baris->sum('terisi'),
            'kosong' => $baris->sum('kosong'),
        ];

        $data = [
            'area' => $area->values(),
            'per_jenis' => $area->groupBy('jenis')->map($jumlahkan),
            'total' => $jumlahkan($area),
        ];

        if ($request->wantsJson()) {
            return response()->json(['data' => $data]);
        }

        return view('laporan.okupansi', $data);
    }

    public function pendapatan(Request $request): JsonResponse|View
    {
        $filter = $request->validate([
            'dari' => ['nullable', 'date_format:Y-m-d'],
            'sampai' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:dari'],
        ]);

        $sampai = isset($filter['sampai']) ? CarbonImmutable::parse($filter['sampai']) : CarbonImmutable::today();
        $dari = isset($filter['dari']) ? CarbonImmutable::parse($filter['dari']) : $sampai->subDays(29);

        $harian = KendaraanParkir::query()
            ->whereNotNull('waktu_keluar')
            ->whereBetween('waktu_keluar', [$dari->startOfDay(), $sampai->endOfDay()])
            ->selectRaw('DATE(waktu_keluar) AS tanggal')
            ->selectRaw('COUNT(*) AS transaksi')
            ->selectRaw("SUM(CASE WHEN jenis = 'mobil' THEN biaya ELSE 0 END) AS mobil")
            ->selectRaw("SUM(CASE WHEN jenis = 'motor' THEN biaya ELSE 0 END) AS motor")
            ->selectRaw('SUM(biaya) AS pendapatan')
            ->groupByRaw('DATE(waktu_keluar)')
            ->orderBy('tanggal')
            ->toBase()
            ->get()
            ->map(fn ($baris) => [
                'tanggal' => (string) $baris->tanggal,
                'transaksi' => (int) $baris->transaksi,
                'mobil' => (int) $baris->mobil,
                'motor' => (int) $baris->motor,
                'pendapatan' => (int) $baris->pendapatan,
            ]);

        $data = [
            'dari' => $dari->toDateString(),
            'sampai' => $sampai->toDateString(),
            'harian' => $harian,
            'total' => [
                'transaksi' => $harian->sum('transaksi'),
                'mobil' => $harian->sum('mobil'),
                'motor' => $harian->sum('motor'),
                'pendapatan' => $harian->sum('pendapatan'),
            ],
        ];

        if ($request->wantsJson()) {
            return response()->json(['data' => $data]);
        }

        return view('laporan.pendapatan', $data);
    }
}
