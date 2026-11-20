<?php

namespace App\Http\Controllers;

use App\Models\Tarif;
use App\Services\KalkulatorTarif;
use Illuminate\View\View;

class PeraturanController extends Controller
{
    /**
     * Angka di halaman ini diambil dari tabel tarif dan config/parkir.php, bukan ditulis ulang.
     */
    public function __invoke(): View
    {
        return view('peraturan', [
            'tarif' => Tarif::orderBy('jenis')->get(),
            'booking' => config('parkir.booking'),
            'menitGratis' => KalkulatorTarif::MENIT_GRATIS,
        ]);
    }
}
