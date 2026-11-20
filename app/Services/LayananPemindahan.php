<?php

namespace App\Services;

use App\Enums\StatusBooking;
use App\Exceptions\BookingDitolak;
use App\Exceptions\ParkirDitolak;
use App\Models\Booking;
use App\Models\SlotParkir;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Admin memindahkan booking ke slot lain yang bebas, misalnya karena slot aslinya masih
 * ditempati kendaraan yang melewati waktu selesai. Pemindahan yang berhasil tidak menimbulkan denda.
 */
class LayananPemindahan
{
    public function __construct(
        private readonly PenyegarStatusBooking $penyegar,
        private readonly LayananKarcis $layananKarcis,
    ) {}

    /**
     * Slot bebas sejenis untuk jendela booking ini: kosong sekarang dan tidak bentrok dengan booking lain.
     * Slot di lantai yang sama didahulukan.
     *
     * @return Collection<int, SlotParkir>
     */
    public function slotPengganti(Booking $booking): Collection
    {
        $booking->loadMissing('slot');

        $bentrok = Booking::berlaku()
            ->whereKeyNot($booking->id)
            ->where('jenis', $booking->jenis)
            ->bentrokDengan($booking->waktu_mulai, $booking->waktu_selesai)
            ->select('slot_id');

        return SlotParkir::with('area')
            ->where('jenis', $booking->jenis)
            ->whereKeyNot($booking->slot_id)
            ->whereDoesntHave('kendaraanAktif')
            ->whereNotIn('id', $bentrok)
            ->get()
            ->sortBy(fn (SlotParkir $s) => [$s->area_id === $booking->slot->area_id ? 0 : 1, $s->area_id, $s->nomor])
            ->values();
    }

    /**
     * Booking yang sedang menunggu langsung check-in di slot baru (kendaraannya sudah di lokasi).
     * Booking yang masih dipesan hanya berganti slot.
     *
     * @throws BookingDitolak|ParkirDitolak
     */
    public function pindah(Booking $booking, int $slotBaruId): Booking
    {
        $this->penyegar->segarkan();

        return DB::transaction(function () use ($booking, $slotBaruId) {
            $booking = Booking::lockForUpdate()->findOrFail($booking->id);
            $slotBaru = SlotParkir::lockForUpdate()->findOrFail($slotBaruId);

            $this->pastikanBisaDipindah($booking, $slotBaru);

            $booking->slot_id = $slotBaru->id;
            $booking->save();

            if ($booking->status === StatusBooking::Menunggu) {
                $this->layananKarcis->masuk($booking);
            }

            return $booking->refresh()->load('slot.area', 'kendaraan');
        });
    }

    /**
     * @throws BookingDitolak
     */
    private function pastikanBisaDipindah(Booking $booking, SlotParkir $slotBaru): void
    {
        if (! in_array($booking->status, [StatusBooking::Dipesan, StatusBooking::Menunggu], true)) {
            throw BookingDitolak::tidakBisaDipindah(strtolower($booking->status->label()));
        }

        if ($slotBaru->id === $booking->slot_id) {
            throw BookingDitolak::slotPenggantiSama();
        }

        if ($slotBaru->jenis !== $booking->jenis) {
            throw BookingDitolak::jenisTidakSesuai($slotBaru->kode_slot, $slotBaru->jenis);
        }

        $terisi = $slotBaru->kendaraanAktif()->exists();
        $bentrok = Booking::berlaku()
            ->whereKeyNot($booking->id)
            ->where('slot_id', $slotBaru->id)
            ->bentrokDengan($booking->waktu_mulai, $booking->waktu_selesai)
            ->exists();

        if ($terisi || $bentrok) {
            throw BookingDitolak::slotPenggantiTidakBebas($slotBaru->kode_slot);
        }
    }
}
