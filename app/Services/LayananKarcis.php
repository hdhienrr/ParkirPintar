<?php

namespace App\Services;

use App\Enums\StatusBooking;
use App\Exceptions\BookingDitolak;
use App\Exceptions\ParkirDitolak;
use App\Models\Booking;
use App\Models\KendaraanParkir;
use App\Models\SlotParkir;
use App\Models\Tarif;
use App\Support\Format;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;

/**
 * Aksi petugas di pintu parkir: check-in dengan kode booking (karcis terbit),
 * check-out (struk terbit, termasuk denda overstay), dan tandai keluar.
 * Aturan K07 untuk slot dan plat tetap dijalankan oleh LayananParkir.
 *
 * Pemindahan booking ke slot lain ada di LayananPemindahan.
 */
class LayananKarcis
{
    private const AWALAN_KARCIS = 'PP';

    public function __construct(
        private readonly LayananParkir $layananParkir,
        private readonly PenyegarStatusBooking $penyegar,
        private readonly KalkulatorDenda $kalkulatorDenda,
        private readonly KalkulatorTarif $kalkulatorTarif,
    ) {}

    /**
     * Booking hari ini yang siap di-check-in untuk satu jenis kendaraan, untuk pilihan di formulir.
     *
     * @return Collection<int, Booking>
     */
    public function siapCheckin(string $jenis): Collection
    {
        $this->penyegar->segarkan();

        return Booking::with('slot')
            ->where('jenis', $jenis)
            ->whereIn('status', [StatusBooking::Dipesan, StatusBooking::Menunggu])
            ->whereDate('waktu_mulai', today())
            ->orderBy('waktu_mulai')
            ->get();
    }

    /**
     * Kendaraan yang sedang di dalam, dengan perkiraan biaya bila keluar sekarang.
     *
     * @return SupportCollection<int, array>
     */
    public function kendaraanDiDalam(): SupportCollection
    {
        $this->penyegar->segarkan();
        $sekarang = now()->startOfSecond();
        $tarif = Tarif::all()->keyBy('jenis');

        return KendaraanParkir::aktif()
            ->with('slot.area', 'booking')
            ->orderBy('waktu_masuk')
            ->get()
            ->map(fn (KendaraanParkir $k) => [
                'kendaraan' => $k,
                'perkiraan' => isset($tarif[$k->jenis])
                    ? $this->kalkulatorTarif->hitung($tarif[$k->jenis], $k->waktu_masuk, $sekarang)
                    : null,
                'melewati_waktu' => (bool) $k->booking?->melewati_waktu,
            ]);
    }

    /**
     * Check-in dengan kode booking. Jika slotnya masih ditempati kendaraan yang melewati
     * waktu selesai, booking berstatus menunggu dan karcis belum terbit.
     *
     * @throws BookingDitolak|ParkirDitolak
     */
    public function checkIn(string $kodeBooking): Booking
    {
        $this->penyegar->segarkan();

        return DB::transaction(function () use ($kodeBooking) {
            $booking = Booking::where('kode_booking', strtoupper(trim($kodeBooking)))->lockForUpdate()->first();

            $this->pastikanBisaCheckin($booking);

            $slot = SlotParkir::lockForUpdate()->findOrFail($booking->slot_id);

            if ($slot->kendaraanAktif()->exists()) {
                $this->tungguSlot($booking);

                return $booking;
            }

            $this->masuk($booking);

            return $booking->refresh()->load('kendaraan.slot.area');
        });
    }

    /**
     * Check-out kendaraan: biaya dari waktu nyata (LayananParkir), denda pelaku bila ada,
     * lalu booking yang menunggu slot ini langsung check-in tanpa denda.
     *
     * @throws ParkirDitolak
     */
    public function checkOut(int $kendaraanId): HasilCheckout
    {
        // Disegarkan dulu: bila batas tunggu sudah lewat, denda pelaku sudah tercatat sebelum keluar.
        $this->penyegar->segarkan();

        return DB::transaction(function () use ($kendaraanId) {
            $kendaraan = $this->layananParkir->checkOut($kendaraanId);
            $booking = $kendaraan->booking;

            if ($booking === null) {
                return new HasilCheckout($kendaraan);
            }

            $booking->status = StatusBooking::Selesai;
            if ($booking->denda_mulai !== null) {
                $booking->denda = $this->kalkulatorDenda->pelaku($this->tarif($booking), $booking->denda_mulai, $kendaraan->waktu_keluar);
            }
            $booking->save();

            return new HasilCheckout($kendaraan, $this->masukkanYangMenunggu($kendaraan->slot_id));
        });
    }

    /**
     * Mencatat keluarnya kendaraan dari booking yang dibatalkan sistem (tidak pernah check-in).
     *
     * @throws BookingDitolak
     */
    public function tandaiKeluar(Booking $booking): Booking
    {
        return DB::transaction(function () use ($booking) {
            $booking = Booking::lockForUpdate()->findOrFail($booking->id);

            if (! $booking->menungguTandaiKeluar()) {
                throw BookingDitolak::tidakBisaTandaiKeluar();
            }

            $keluar = now()->startOfSecond();
            $booking->waktu_keluar = $keluar;
            $booking->denda = $booking->denda_mulai !== null
                ? $this->kalkulatorDenda->korban($this->tarif($booking), $booking->denda_mulai, $keluar)
                : 0;
            $booking->save();

            return $booking;
        });
    }

    /**
     * @throws BookingDitolak
     */
    private function pastikanBisaCheckin(?Booking $booking): void
    {
        if ($booking === null) {
            throw BookingDitolak::tidakDitemukan();
        }

        if (! in_array($booking->status, [StatusBooking::Dipesan, StatusBooking::Menunggu], true)) {
            throw BookingDitolak::tidakBisaCheckin(strtolower($booking->status->label()));
        }

        $palingCepat = $booking->waktu_mulai->copy()->subMinutes(config('parkir.booking.toleransi_awal_menit'));
        if (now()->lessThan($palingCepat)) {
            throw BookingDitolak::terlaluAwal(Format::jam($palingCepat));
        }
    }

    /**
     * Hitungan tunggu dimulai dari yang lebih akhir antara kedatangan dan waktu mulai booking,
     * jadi booking tidak bisa dibatalkan sistem sebelum jadwalnya mulai.
     */
    private function tungguSlot(Booking $booking): void
    {
        if ($booking->status === StatusBooking::Menunggu) {
            return;
        }

        $booking->forceFill([
            'status' => StatusBooking::Menunggu,
            'menunggu_sejak' => now()->max($booking->waktu_mulai)->startOfSecond(),
        ])->save();
    }

    /**
     * Mencatat kendaraan booking ini masuk ke slotnya: aturan K07 lewat LayananParkir, lalu karcis terbit.
     * Dipakai juga oleh LayananPemindahan setelah booking yang menunggu dipindah.
     *
     * @throws ParkirDitolak
     */
    public function masuk(Booking $booking): KendaraanParkir
    {
        $kendaraan = $this->layananParkir->checkIn($booking->slot_id, $booking->plat, $booking->jenis);

        $kendaraan->update([
            'booking_id' => $booking->id,
            'nomor_karcis' => $this->nomorKarcisBaru($kendaraan->waktu_masuk),
        ]);

        $booking->status = StatusBooking::Aktif;
        $booking->save();

        return $kendaraan;
    }

    private function masukkanYangMenunggu(int $slotId): ?Booking
    {
        $menunggu = Booking::where('slot_id', $slotId)
            ->where('status', StatusBooking::Menunggu)
            ->orderBy('menunggu_sejak')
            ->lockForUpdate()
            ->first();

        if ($menunggu === null) {
            return null;
        }

        try {
            $this->masuk($menunggu);
        } catch (ParkirDitolak) {
            // Misalnya platnya masih tercatat di slot lain. Check-out tetap jalan,
            // booking itu tetap menunggu dan diselesaikan petugas lewat check-in biasa.
            return null;
        }

        return $menunggu->load('kendaraan');
    }

    /** PP-20261121-0001, urut per hari. */
    private function nomorKarcisBaru(CarbonInterface $waktuMasuk): string
    {
        $awalan = self::AWALAN_KARCIS.'-'.$waktuMasuk->format('Ymd').'-';

        $terakhir = KendaraanParkir::where('nomor_karcis', 'like', $awalan.'%')
            ->orderByDesc('nomor_karcis')
            ->lockForUpdate()
            ->value('nomor_karcis');

        $urut = $terakhir === null ? 1 : (int) substr($terakhir, strlen($awalan)) + 1;

        return $awalan.sprintf('%04d', $urut);
    }

    private function tarif(Booking $booking): Tarif
    {
        return Tarif::where('jenis', $booking->jenis)->firstOrFail();
    }
}
