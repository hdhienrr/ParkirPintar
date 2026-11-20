<?php

use App\Http\Controllers\AreaController;
use App\Http\Controllers\BerandaController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\ParkirController;
use Illuminate\Support\Facades\Route;

Route::get('/', BerandaController::class)->name('beranda');

Route::get('/parkir/{jenis}', [ParkirController::class, 'halaman'])->whereIn('jenis', ['mobil', 'motor'])->name('parkir');
Route::post('/checkin', [ParkirController::class, 'checkin'])->name('checkin');
Route::post('/checkout/{id}', [ParkirController::class, 'checkout'])->whereNumber('id')->name('checkout');

Route::get('/areas', [AreaController::class, 'index'])->name('areas.index');
Route::post('/areas', [AreaController::class, 'store'])->name('areas.store');
Route::put('/areas/{area}', [AreaController::class, 'update'])->whereNumber('area')->name('areas.update');
Route::delete('/areas/{area}', [AreaController::class, 'destroy'])->whereNumber('area')->name('areas.destroy');
Route::get('/areas/{area}/slots', [AreaController::class, 'slots'])->whereNumber('area')->name('areas.slots');

Route::get('/reports/occupancy', [LaporanController::class, 'okupansi'])->name('reports.occupancy');
Route::get('/reports/revenue', [LaporanController::class, 'pendapatan'])->name('reports.revenue');
