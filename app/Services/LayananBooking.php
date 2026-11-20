<?php

namespace App\Services;

use App\Enums\StatusBooking;
use App\Exceptions\BookingDitolak;
use App\Models\AreaParkir;
use App\Models\Booking;
use App\Models\KendaraanParkir;
use App\Models\SlotParkir;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LayananBooking
{
    /** Huruf dan angka kode booking, tanpa 0/O dan 1/I yang mudah tertukar. */
    private const HURUF_KODE = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    private const PANJANG_KODE = 8;

    /**
     * @throws BookingDitolak
     */
    public function buat(User $user, string $plat, string $jenis, int $slotId, CarbonImmutable $mulai, int $durasiJam): Booking
    {
        $this->segarkanStatus();
        $selesai = $this->pastikanJadwalValid($mulai, $durasiJam);
        $plat = KendaraanParkir::normalisasiPlat($plat);

        return DB::transaction(function () use ($user, $plat, $jenis, $slotId, $mulai, $selesai) {
            // Lock akun dan slot, supaya dua permintaan bersamaan diproses bergantian.
            User::whereKey($user->id)->lockForUpdate()->first();
            $slot = SlotParkir::lockForUpdate()->findOrFail($slotId);

            $this->pastikanBolehBooking($user, $plat, $jenis, $slot, $mulai, $selesai);

            return Booking::create([
                'user_id' => $user->id,
                'kode_booking' => $this->kodeBaru(),
                'slot_id' => $slot->id,
                'plat' => $plat,
                'jenis' => $jenis,
                'waktu_mulai' => $mulai,
                'waktu_selesai' => $selesai,
            ]);
        });
    }

    /**
     * @throws BookingDitolak
     */
    public function batal(Booking $booking): Booking
    {
        return DB::transaction(function () use ($booking) {
            $booking = Booking::lockForUpdate()->findOrFail($booking->id);

            if (! $booking->bisaDibatalkan()) {
                throw BookingDitolak::tidakBisaDibatalkan();
            }

            $booking->status = StatusBooking::Dibatalkan;
            $booking->save();

            return $booking;
        });
    }

    /**
     * Denah semua lantai satu jenis kendaraan, dengan slot yang sudah dibooking pada jendela itu ditandai terisi.
     *
     * @throws BookingDitolak jika jadwalnya sendiri tidak valid
     */
    public function ketersediaan(string $jenis, CarbonImmutable $mulai, int $durasiJam): Collection
    {
        $this->segarkanStatus();
        $selesai = $this->pastikanJadwalValid($mulai, $durasiJam);

        $terpakai = $this->slotTerpakai($jenis, $mulai, $selesai)->pluck('slot_id')->flip();

        return AreaParkir::jenis($jenis)->with('slot')->orderBy('nama')->get()
            ->map(function (AreaParkir $area) use ($terpakai) {
                $slot = $area->slot->sortBy('nomor')->values()->map(fn (SlotParkir $s) => [
                    'id' => $s->id,
                    'kode_slot' => $s->kode_slot,
                    'nomor' => $s->nomor,
                    ...$s->posisi(),
                    'status' => $terpakai->has($s->id) ? 'terisi' : 'kosong',
                ]);

                return [
                    'id' => $area->id,
                    'nama' => $area->nama,
                    'kosong' => $slot->where('status', 'kosong')->count(),
                    'total_slot' => $slot->count(),
                    'slot' => $slot,
                ];
            });
    }

    /**
     * @return EloquentCollection<int, Booking>
     */
    public function milik(User $user): EloquentCollection
    {
        $this->segarkanStatus();

        return Booking::where('user_id', $user->id)->with('slot.area')->latest('waktu_mulai')->get();
    }

    /**
     * Jam bulat terdekat yang masih bisa dibooking, untuk isian awal formulir.
     */
    public function jadwalTerdekat(): CarbonImmutable
    {
        $aturan = config('parkir.booking');
        $mulai = CarbonImmutable::now()->addHour()->startOfHour();

        if ($mulai->hour < $aturan['jam_buka']) {
            return $mulai->setTime($aturan['jam_buka'], 0);
        }

        if ($mulai->hour > $aturan['jam_tutup'] - $aturan['durasi_min_jam']) {
            return $mulai->addDay()->setTime($aturan['jam_buka'], 0);
        }

        return $mulai;
    }

    public function detail(Booking $booking): Booking
    {
        $this->segarkanStatus();

        return $booking->refresh()->load('slot.area', 'user');
    }

    /**
     * Status yang bergantung pada waktu dihitung saat dibutuhkan, tanpa scheduler:
     * booking yang tidak pernah dicoba check-in sampai batas terlambat menjadi hangus.
     * Booking yang sedang menunggu tidak ikut, karena check-in sudah dicoba.
     */
    public function segarkanStatus(): void
    {
        Booking::where('status', StatusBooking::Dipesan)
            ->where('waktu_mulai', '<', now()->subMinutes(config('parkir.booking.toleransi_terlambat_menit')))
            ->update(['status' => StatusBooking::Hangus]);
    }

    /**
     * @return CarbonImmutable waktu selesai
     *
     * @throws BookingDitolak
     */
    private function pastikanJadwalValid(CarbonImmutable $mulai, int $durasiJam): CarbonImmutable
    {
        $aturan = config('parkir.booking');

        if ($mulai->minute !== 0 || $mulai->second !== 0) {
            throw BookingDitolak::jamTidakBulat();
        }

        if ($durasiJam < $aturan['durasi_min_jam'] || $durasiJam > $aturan['durasi_maks_jam']) {
            throw BookingDitolak::durasiTidakValid($aturan['durasi_min_jam'], $aturan['durasi_maks_jam']);
        }

        if ($mulai->lessThanOrEqualTo(now())) {
            throw BookingDitolak::waktuSudahLewat();
        }

        // Dihitung per tanggal, sama dengan batas tanggal di formulir.
        if ($mulai->startOfDay()->greaterThan(today()->addDays($aturan['hari_ke_depan_maks']))) {
            throw BookingDitolak::terlaluJauh($aturan['hari_ke_depan_maks']);
        }

        $selesai = $mulai->addHours($durasiJam);
        $tutup = $mulai->setTime($aturan['jam_tutup'], 0);

        if ($mulai->hour < $aturan['jam_buka'] || $selesai->greaterThan($tutup)) {
            throw BookingDitolak::diLuarJamOperasional($aturan['jam_buka'], $aturan['jam_tutup']);
        }

        return $selesai;
    }

    /**
     * @throws BookingDitolak
     */
    private function pastikanBolehBooking(User $user, string $plat, string $jenis, SlotParkir $slot, CarbonImmutable $mulai, CarbonImmutable $selesai): void
    {
        if ($slot->jenis !== $jenis) {
            throw BookingDitolak::jenisTidakSesuai($slot->kode_slot, $slot->jenis);
        }

        if (Booking::berlaku()->where('user_id', $user->id)->exists()) {
            throw BookingDitolak::akunMasihPunyaBooking();
        }

        $platMaks = config('parkir.booking.plat_maks_aktif');
        if (Booking::berlaku()->where('plat', $plat)->count() >= $platMaks) {
            throw BookingDitolak::platMencapaiBatas($plat, $platMaks);
        }

        if (Booking::berlaku()->where('slot_id', $slot->id)->bentrokDengan($mulai, $selesai)->exists()) {
            $adaSlotLain = SlotParkir::where('jenis', $jenis)
                ->whereNotIn('id', $this->slotTerpakai($jenis, $mulai, $selesai)->select('slot_id'))
                ->exists();

            throw $adaSlotLain ? BookingDitolak::slotTerbooking($slot->kode_slot) : BookingDitolak::semuaSlotPenuh($jenis);
        }
    }

    private function slotTerpakai(string $jenis, CarbonImmutable $mulai, CarbonImmutable $selesai): Builder
    {
        return Booking::berlaku()->where('jenis', $jenis)->bentrokDengan($mulai, $selesai);
    }

    private function kodeBaru(): string
    {
        do {
            $acak = '';
            for ($i = 0; $i < self::PANJANG_KODE; $i++) {
                $acak .= self::HURUF_KODE[random_int(0, strlen(self::HURUF_KODE) - 1)];
            }
            $kode = "BK-{$acak}";
        } while (Booking::where('kode_booking', $kode)->exists());

        return $kode;
    }
}
