<?php

namespace App\Policies;

use App\Models\KendaraanParkir;
use App\Models\User;

class KendaraanParkirPolicy
{
    /** Karcis dan struk: pemilik booking-nya sendiri, atau admin. */
    public function view(User $user, KendaraanParkir $kendaraan): bool
    {
        return $user->adalahAdmin() || $kendaraan->booking?->user_id === $user->id;
    }
}
