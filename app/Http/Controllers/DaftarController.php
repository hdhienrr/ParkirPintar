<?php

namespace App\Http\Controllers;

use App\Http\Requests\DaftarRequest;
use App\Services\LayananAkun;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DaftarController extends Controller
{
    public function create(): View
    {
        return view('auth.daftar');
    }

    public function store(DaftarRequest $request, LayananAkun $layanan): RedirectResponse
    {
        $data = $request->validated();
        $user = $layanan->daftar($data['name'], $data['email'], $data['password']);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect(SesiController::halamanAwal($user))->with('status', 'Akun berhasil dibuat.');
    }
}
