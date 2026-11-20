<?php

namespace App\Http\Controllers;

use App\Models\Tarif;
use App\Services\KalkulatorTarif;
use App\Services\LayananArea;
use Illuminate\View\View;

class BerandaController extends Controller
{
    public function __invoke(LayananArea $layananArea, KalkulatorTarif $kalkulator): View
    {
        $area = $layananArea->daftarRingkasan();
        $tarif = Tarif::orderBy('jenis')->get()->keyBy('jenis');
        $menitContoh = config('parkir.beranda.menit_contoh_biaya');

        return view('beranda', [
            'area' => $area,
            'tarif' => $tarif,
            'menitContoh' => $menitContoh,
            'contohBiaya' => $tarif->map(fn (Tarif $t) => $kalkulator->biayaUntukMenit($t, $menitContoh)),
            'totalKosong' => $area->sum('kosong'),
            'totalSlot' => $area->sum('total_slot'),
        ]);
    }
}
