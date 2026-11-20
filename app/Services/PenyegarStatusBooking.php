<?php

namespace App\Services;

use App\Enums\StatusBooking;
use App\Models\Booking;
use Illuminate\Support\Facades\DB;

/**
 * Status booking yang bergantung pada waktu dihitung saat dibutuhkan (lazy), tanpa scheduler atau job.
 * Dipanggil sebelum halaman atau aksi yang membaca status booking.
 */
class PenyegarStatusBooking
{
    public function segarkan(): void
    {
        $this->tandaiHangus();
        $this->batalkanYangMenungguTerlaluLama();
    }

    /**
     * Booking yang tidak pernah dicoba check-in sampai batas terlambat menjadi hangus, tanpa denda.
     * Booking yang sedang menunggu tidak ikut, karena check-in sudah dicoba.
     */
    private function tandaiHangus(): void
    {
        Booking::where('status', StatusBooking::Dipesan)
            ->where('waktu_mulai', '<', now()->subMinutes(config('parkir.booking.toleransi_terlambat_menit')))
            ->update(['status' => StatusBooking::Hangus]);
    }

    /**
     * Booking yang menunggu slotnya kosong lebih lama dari batas, sementara kendaraan penghalang
     * masih di dalam, dibatalkan sistem. Denda pelaku mulai berjalan pada booking penghalang,
     * dan denda korban baru berjalan bila kendaraan korban tidak keluar dalam batas waktu.
     */
    private function batalkanYangMenungguTerlaluLama(): void
    {
        $aturan = config('parkir.booking');

        $kadaluwarsa = Booking::where('status', StatusBooking::Menunggu)
            ->where('menunggu_sejak', '<=', now()->subMinutes($aturan['tunggu_penghalang_menit']))
            ->pluck('id');

        foreach ($kadaluwarsa as $id) {
            DB::transaction(function () use ($id, $aturan) {
                $booking = Booking::lockForUpdate()->find($id);

                if ($booking?->status !== StatusBooking::Menunggu) {
                    return;
                }

                $penghalang = $booking->slot->kendaraanAktif;

                // Slot sudah kosong: booking tetap menunggu dan bisa langsung di-check-in admin.
                if ($penghalang === null) {
                    return;
                }

                $dibatalkanPada = $booking->menunggu_sejak->copy()->addMinutes($aturan['tunggu_penghalang_menit']);

                $booking->forceFill([
                    'status' => StatusBooking::DibatalkanSistem,
                    'alasan' => "Slot {$booking->slot->kode_slot} masih ditempati kendaraan yang melewati waktu selesai.",
                    'penyebab_booking_id' => $penghalang->booking_id,
                    'denda_mulai' => $dibatalkanPada->copy()->addMinutes($aturan['keluar_tanpa_denda_menit']),
                ])->save();

                if ($penghalang->booking !== null && $penghalang->booking->denda_mulai === null) {
                    $penghalang->booking->forceFill(['denda_mulai' => $dibatalkanPada])->save();
                }
            });
        }
    }
}
