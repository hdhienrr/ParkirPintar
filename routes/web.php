<?php

use App\Enums\JenisKendaraan;
use App\Http\Controllers\AdminBookingController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\BerandaController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\DaftarController;
use App\Http\Controllers\KarcisController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\ParkirController;
use App\Http\Controllers\PeraturanController;
use App\Http\Controllers\SesiController;
use Illuminate\Support\Facades\Route;

// Publik
Route::get('/', BerandaController::class)->name('beranda');
Route::get('/peraturan', PeraturanController::class)->name('peraturan');

Route::middleware('guest')->group(function () {
    Route::get('/login', [SesiController::class, 'create'])->name('login');
    Route::post('/login', [SesiController::class, 'store']);
    Route::get('/register', [DaftarController::class, 'create'])->name('register');
    Route::post('/register', [DaftarController::class, 'store']);
});

Route::post('/logout', [SesiController::class, 'destroy'])->middleware('auth')->name('logout');

// User: booking milik sendiri
Route::middleware(['auth', 'can:booking'])->group(function () {
    Route::get('/booking/baru', [BookingController::class, 'create'])->name('booking.create');
    Route::get('/booking/ketersediaan', [BookingController::class, 'ketersediaan'])->name('booking.ketersediaan');
    Route::post('/booking', [BookingController::class, 'store'])->name('booking.store');
    Route::get('/booking-saya', [BookingController::class, 'index'])->name('booking.index');
    Route::post('/booking/{booking}/batal', [BookingController::class, 'batal'])->can('batal', 'booking')->name('booking.batal');
});

// Bukti booking: pemilik atau admin
Route::get('/booking/{booking}', [BookingController::class, 'show'])
    ->middleware('auth')
    ->can('view', 'booking')
    ->name('booking.show');

// Karcis parkir dan struk keluar: pemilik booking atau admin
Route::get('/karcis/{kendaraan:nomor_karcis}', [KarcisController::class, 'show'])
    ->middleware('auth')
    ->can('view', 'kendaraan')
    ->name('karcis.show');

// Admin: semua fitur v1, check-in dengan kode booking
Route::middleware(['auth', 'can:admin'])->group(function () {
    Route::get('/parkir/{jenis}', [ParkirController::class, 'halaman'])->whereIn('jenis', JenisKendaraan::nilai())->name('parkir');
    Route::post('/checkin', [ParkirController::class, 'checkin'])->name('checkin');
    Route::post('/checkout/{id}', [ParkirController::class, 'checkout'])->whereNumber('id')->name('checkout');

    Route::get('/admin/booking', [AdminBookingController::class, 'index'])->name('admin.booking.index');
    Route::get('/admin/booking/sedang-parkir', [AdminBookingController::class, 'sedangParkir'])->name('admin.booking.parkir');
    Route::get('/admin/booking/{booking}/pindah', [AdminBookingController::class, 'formPindah'])->name('admin.booking.pindah.form');
    Route::post('/admin/booking/{booking}/pindah', [AdminBookingController::class, 'pindah'])->name('admin.booking.pindah');
    Route::post('/admin/booking/{booking}/keluar', [AdminBookingController::class, 'keluar'])->name('admin.booking.keluar');

    Route::get('/areas', [AreaController::class, 'index'])->name('areas.index');
    Route::post('/areas', [AreaController::class, 'store'])->name('areas.store');
    Route::put('/areas/{area}', [AreaController::class, 'update'])->whereNumber('area')->name('areas.update');
    Route::delete('/areas/{area}', [AreaController::class, 'destroy'])->whereNumber('area')->name('areas.destroy');
    Route::get('/areas/{area}/slots', [AreaController::class, 'slots'])->whereNumber('area')->name('areas.slots');

    Route::get('/reports/occupancy', [LaporanController::class, 'okupansi'])->name('reports.occupancy');
    Route::get('/reports/revenue', [LaporanController::class, 'pendapatan'])->name('reports.revenue');
});
