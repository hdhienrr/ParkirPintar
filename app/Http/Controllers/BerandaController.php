<?php

namespace App\Http\Controllers;

use App\Models\AreaParkir;
use App\Models\Tarif;
use App\Services\KalkulatorTarif;
use Illuminate\View\View;

class BerandaController extends Controller
{
    /** Lama parkir untuk contoh biaya di bagian tarif: 2 jam 30 menit. */
    private const MENIT_CONTOH = 150;

    public function __invoke(KalkulatorTarif $kalkulator): View
    {
        $area = AreaParkir::denganRingkasan()->orderBy('nama')->get()->map->ringkasan();
        $tarif = Tarif::orderBy('jenis')->get()->keyBy('jenis');

        return view('beranda', [
            'area' => $area,
            'tarif' => $tarif,
            'contohBiaya' => $tarif->map(fn (Tarif $t) => $kalkulator->biayaUntukMenit($t, self::MENIT_CONTOH)),
            'totalKosong' => $area->sum('kosong'),
            'totalSlot' => $area->sum('total_slot'),
        ]);
    }
}
