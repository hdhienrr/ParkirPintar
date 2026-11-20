<?php

use App\Enums\JenisKendaraan;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\BerandaController;
use App\Http\Controllers\DaftarController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\ParkirController;
use App\Http\Controllers\SesiController;
use Illuminate\Support\Facades\Route;

// Publik
Route::get('/', BerandaController::class)->name('beranda');

Route::middleware('guest')->group(function () {
    Route::get('/login', [SesiController::class, 'create'])->name('login');
    Route::post('/login', [SesiController::class, 'store']);
    Route::get('/register', [DaftarController::class, 'create'])->name('register');
    Route::post('/register', [DaftarController::class, 'store']);
});

Route::post('/logout', [SesiController::class, 'destroy'])->middleware('auth')->name('logout');

// Admin: semua fitur v1
Route::middleware(['auth', 'can:admin'])->group(function () {
    Route::get('/parkir/{jenis}', [ParkirController::class, 'halaman'])->whereIn('jenis', JenisKendaraan::nilai())->name('parkir');
    Route::post('/checkin', [ParkirController::class, 'checkin'])->name('checkin');
    Route::post('/checkout/{id}', [ParkirController::class, 'checkout'])->whereNumber('id')->name('checkout');

    Route::get('/areas', [AreaController::class, 'index'])->name('areas.index');
    Route::post('/areas', [AreaController::class, 'store'])->name('areas.store');
    Route::put('/areas/{area}', [AreaController::class, 'update'])->whereNumber('area')->name('areas.update');
    Route::delete('/areas/{area}', [AreaController::class, 'destroy'])->whereNumber('area')->name('areas.destroy');
    Route::get('/areas/{area}/slots', [AreaController::class, 'slots'])->whereNumber('area')->name('areas.slots');

    Route::get('/reports/occupancy', [LaporanController::class, 'okupansi'])->name('reports.occupancy');
    Route::get('/reports/revenue', [LaporanController::class, 'pendapatan'])->name('reports.revenue');
});
