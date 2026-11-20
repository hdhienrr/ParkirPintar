<?php

namespace App\Http\Controllers;

use App\Services\LayananKarcis;
use Illuminate\View\View;

class AdminKendaraanController extends Controller
{
    /**
     * Kendaraan yang sedang parkir, dengan label merah bila sudah melewati waktu selesai booking.
     */
    public function index(LayananKarcis $layanan): View
    {
        return view('admin.kendaraan', ['daftar' => $layanan->kendaraanDiDalam()]);
    }
}
