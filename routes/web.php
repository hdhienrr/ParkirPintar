<?php

use App\Http\Controllers\ParkirController;
use Illuminate\Support\Facades\Route;

// Sementara, sampai halaman K07 dibuat.
Route::view('/', 'welcome');

Route::post('/checkin', [ParkirController::class, 'checkin'])->name('checkin');
Route::post('/checkout/{id}', [ParkirController::class, 'checkout'])->whereNumber('id')->name('checkout');
