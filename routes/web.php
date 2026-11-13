<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookingController;

Route::get('/', [BookingController::class, 'index'])->name('dashboard');
Route::post('/booking', [BookingController::class, 'store'])->name('booking.store');