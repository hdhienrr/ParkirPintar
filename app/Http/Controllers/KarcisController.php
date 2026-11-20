<?php

namespace App\Http\Controllers;

use App\Models\KendaraanParkir;
use App\Models\Tarif;
use Illuminate\View\View;

class KarcisController extends Controller
{
    /**
     * Karcis parkir, ditambah struk keluar setelah check-out.
     */
    public function show(KendaraanParkir $kendaraan): View
    {
        $kendaraan->load('slot.area', 'booking');

        return view('karcis', [
            'kendaraan' => $kendaraan,
            'tarif' => Tarif::where('jenis', $kendaraan->jenis)->first(),
        ]);
    }
}
