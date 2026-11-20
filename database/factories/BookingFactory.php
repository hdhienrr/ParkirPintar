<?php

namespace Database\Factories;

use App\Enums\StatusBooking;
use App\Models\Booking;
use App\Models\SlotParkir;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * Booking besok pukul 08.00 sampai 10.00, status dipesan.
 *
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    public function definition(): array
    {
        $mulai = now()->addDay()->setTime(8, 0);

        return [
            'user_id' => User::factory(),
            'kode_booking' => 'BK-'.fake()->unique()->bothify('########'),
            'slot_id' => SlotParkir::factory(),
            'plat' => fake()->unique()->bothify('B #### ??'),
            'jenis' => fn (array $atribut) => SlotParkir::findOrFail($atribut['slot_id'])->jenis,
            'waktu_mulai' => $mulai,
            'waktu_selesai' => $mulai->copy()->addHours(2),
        ];
    }

    public function berstatus(StatusBooking $status): static
    {
        return $this->afterMaking(fn (Booking $booking) => $booking->status = $status);
    }

    public function jendela(string $mulai, int $jam): static
    {
        return $this->state(fn () => [
            'waktu_mulai' => Carbon::parse($mulai),
            'waktu_selesai' => Carbon::parse($mulai)->addHours($jam),
        ]);
    }
}
