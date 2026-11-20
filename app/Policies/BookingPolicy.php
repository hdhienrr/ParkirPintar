<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    /** Bukti booking: pemiliknya sendiri, atau admin. */
    public function view(User $user, Booking $booking): bool
    {
        return $user->adalahAdmin() || $booking->user_id === $user->id;
    }

    /** Tombol tandai keluar: hanya admin, dan hanya selagi booking itu memang menunggu dicatat keluar. */
    public function tandaiKeluar(User $user, Booking $booking): bool
    {
        return $user->adalahAdmin() && $booking->menungguTandaiKeluar();
    }

    /** Pembatalan hanya oleh pemiliknya. */
    public function batal(User $user, Booking $booking): bool
    {
        return $booking->user_id === $user->id;
    }
}
