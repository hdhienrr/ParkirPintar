<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ParkingSpot;
use App\Models\Booking;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    public function index()
    {
        $spots = ParkingSpot::all();
        $recentBookings = Booking::with('parkingSpot')->latest()->take(10)->get();

        return view('dashboard', compact('spots', 'recentBookings'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'parking_spot_id' => 'required|exists:parking_spots,id',
            'plate_number'    => 'required|string|max:15',
            'duration_hours'  => 'required|integer|min:1|max:24',
        ]);

        $spot = ParkingSpot::findOrFail($validated['parking_spot_id']);

        if ($spot->status !== 'available') {
            return back()->with('error', 'Maaf, slot parkir ini sudah dipesan/terisi!');
        }

        $totalPrice = $spot->hourly_rate * $validated['duration_hours'];

        DB::transaction(function () use ($validated, $spot, $totalPrice, &$booking) {
            $booking = Booking::create([
                'booking_code'    => 'BK-' . strtoupper(Str::random(5)),
                'parking_spot_id' => $spot->id,
                'plate_number'    => strtoupper($validated['plate_number']),
                'vehicle_type'    => $spot->vehicle_type,
                'duration_hours'  => $validated['duration_hours'],
                'total_price'     => $totalPrice,
                'status'          => 'pending',
            ]);

            $spot->update(['status' => 'pending']);
        });

        return back()->with('success', 'Booking berhasil dibuat dengan status PENDING! Kode: ' . $booking->booking_code);
    }
}