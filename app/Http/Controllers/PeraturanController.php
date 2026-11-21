<?php

namespace App\Http\Controllers;

use App\Models\Tarif;
use Carbon\CarbonImmutable;
use Illuminate\View\View;

class PeraturanController extends Controller
{
    /**
     * Angka di halaman ini diambil dari tabel tarif dan config/parkir.php, bukan ditulis ulang.
     */
    public function __invoke(): View
    {
        $booking = config('parkir.booking');
        $mulai = CarbonImmutable::today()->setTime(config('parkir.peraturan.jam_contoh'), 0);
        $batasTerlambat = $mulai->addMinutes($booking['toleransi_terlambat_menit']);

        return view('peraturan', [
            'tarif' => Tarif::orderBy('jenis')->get(),
            'booking' => $booking,
            'contoh' => [
                'mulai' => $mulai,
                'paling_cepat' => $mulai->subMinutes($booking['toleransi_awal_menit']),
                'batas_terlambat' => $batasTerlambat,
                'hangus' => $batasTerlambat->addMinute(),
            ],
        ]);
    }
}
