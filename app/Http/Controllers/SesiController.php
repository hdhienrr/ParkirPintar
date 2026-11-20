<?php

namespace App\Http\Controllers;

use App\Http\Requests\MasukRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SesiController extends Controller
{
    public function create(): View
    {
        return view('auth.masuk');
    }

    public function store(MasukRequest $request): RedirectResponse
    {
        $request->masuk();

        // Session baru setelah masuk, supaya ID session lama tidak bisa dipakai orang lain.
        $request->session()->regenerate();

        return redirect()->intended(self::halamanAwal($request->user()));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('beranda');
    }

    public static function halamanAwal(User $user): string
    {
        return $user->adalahAdmin() ? route('parkir', 'mobil') : route('booking.index');
    }
}
