<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    protected $fillable = [
        'booking_code',
        'parking_spot_id',
        'plate_number',
        'vehicle_type',
        'duration_hours',
        'total_price',
        'status',
    ];

    public function parkingSpot(): BelongsTo
    {
        return $this->belongsTo(ParkingSpot::class);
    }
}