<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ParkingSpot extends Model
{
    protected $fillable = [
        'spot_code',
        'vehicle_type',
        'status',
        'hourly_rate',
    ];

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}