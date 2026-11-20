<?php

namespace App\Services;

use App\Exceptions\ParkirDitolak;
use App\Models\AreaParkir;
use App\Models\KendaraanParkir;
use App\Models\SlotParkir;
use App\Models\Tarif;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LayananArea
{
    public function __construct(private readonly KalkulatorTarif $kalkulator) {}

    /**
     * Ringkasan slot kosong dan terisi semua area, urut nama.
     */
    public function daftarRingkasan(?string $jenis = null): Collection
    {
        return AreaParkir::denganRingkasan()
            ->when($jenis, fn ($query) => $query->jenis($jenis))
            ->orderBy('nama')
            ->get()
            ->map->ringkasan();
    }

    public function ringkasan(AreaParkir $area): array
    {
        return AreaParkir::denganRingkasan()->findOrFail($area->id)->ringkasan();
    }

    /**
     * Membuat area beserta slot bernomor 1 sampai jumlah slot.
     *
     * @throws ValidationException jika lantai itu sudah ada untuk jenis yang sama
     */
    public function buat(string $nama, string $jenis, int $lantai, int $jumlahSlot): AreaParkir
    {
        return DB::transaction(function () use ($nama, $jenis, $lantai, $jumlahSlot) {
            $kodePertama = SlotParkir::buatKode($jenis, $lantai, 1);

            if (SlotParkir::where('kode_slot', $kodePertama)->lockForUpdate()->exists()) {
                throw ValidationException::withMessages([
                    'lantai' => "Lantai {$lantai} untuk {$jenis} sudah ada.",
                ]);
            }

            $area = AreaParkir::create(['nama' => $nama]);
            $this->tambahSlot($area, $jenis, $lantai, 1, $jumlahSlot);

            return $area;
        });
    }

    /**
     * @throws ValidationException jika slot yang dikurangi pernah dipakai
     */
    public function ubah(AreaParkir $area, string $nama, ?int $jumlahSlot): AreaParkir
    {
        DB::transaction(function () use ($area, $nama, $jumlahSlot) {
            $area->update(['nama' => $nama]);

            if ($jumlahSlot !== null) {
                $this->aturJumlahSlot($area, $jumlahSlot);
            }
        });

        return $area;
    }

    /**
     * @throws ParkirDitolak jika area punya riwayat parkir
     */
    public function hapus(AreaParkir $area): void
    {
        if ($area->kendaraan()->exists()) {
            throw new ParkirDitolak("{$area->nama} punya riwayat parkir, jadi tidak bisa dihapus.");
        }

        $area->delete();
    }

    /**
     * Denah satu area: posisi tiap slot, status, dan kendaraan yang menempati
     * beserta perkiraan biaya bila keluar sekarang.
     */
    public function denah(AreaParkir $area): array
    {
        $ringkasan = $this->ringkasan($area);
        $tarif = Tarif::where('jenis', $ringkasan['jenis'])->first();
        $sekarang = now()->startOfSecond();

        $slot = $area->slot()->with('kendaraanAktif')->get()->sortBy('nomor')->values()
            ->map(fn (SlotParkir $slot) => [
                'id' => $slot->id,
                'kode_slot' => $slot->kode_slot,
                'nomor' => $slot->nomor,
                ...$slot->posisi(),
                'status' => $slot->kendaraanAktif ? 'terisi' : 'kosong',
                'kendaraan' => $slot->kendaraanAktif
                    ? $this->kendaraanDiSlot($slot->kendaraanAktif, $tarif, $sekarang)
                    : null,
            ]);

        return ['area' => $ringkasan, 'slot' => $slot];
    }

    private function kendaraanDiSlot(KendaraanParkir $kendaraan, ?Tarif $tarif, CarbonInterface $sekarang): array
    {
        $perkiraan = $tarif
            ? $this->kalkulator->hitung($tarif, $kendaraan->waktu_masuk, $sekarang)
            : ['durasi_menit' => null, 'biaya' => null];

        return [
            'id' => $kendaraan->id,
            'plat' => $kendaraan->plat,
            'waktu_masuk' => $kendaraan->waktu_masuk->toIso8601String(),
            'durasi_menit' => $perkiraan['durasi_menit'],
            'perkiraan_biaya' => $perkiraan['biaya'],
        ];
    }

    private function tambahSlot(AreaParkir $area, string $jenis, int $lantai, int $dari, int $sampai): void
    {
        $sekarang = now();

        $baris = array_map(fn (int $nomor) => [
            'area_id' => $area->id,
            'kode_slot' => SlotParkir::buatKode($jenis, $lantai, $nomor),
            'jenis' => $jenis,
            'created_at' => $sekarang,
            'updated_at' => $sekarang,
        ], range($dari, $sampai));

        SlotParkir::insert($baris);
    }

    /**
     * Menambah slot di belakang, atau mengurangi slot bernomor terbesar
     * selama slot itu belum pernah dipakai.
     */
    private function aturJumlahSlot(AreaParkir $area, int $jumlahBaru): void
    {
        $slot = $area->slot()->lockForUpdate()->get()->sortBy('nomor')->values();
        $jumlahLama = $slot->count();

        if ($jumlahBaru > $jumlahLama) {
            $pertama = $slot->first();
            $this->tambahSlot($area, $pertama->jenis, $pertama->lantai, $jumlahLama + 1, $jumlahBaru);

            return;
        }

        $idDihapus = $slot->filter(fn (SlotParkir $s) => $s->nomor > $jumlahBaru)->pluck('id');

        if ($idDihapus->isEmpty()) {
            return;
        }

        if (KendaraanParkir::whereIn('slot_id', $idDihapus)->exists()) {
            throw ValidationException::withMessages([
                'jumlah_slot' => 'Slot yang akan dikurangi punya riwayat parkir, jadi jumlah slot tidak bisa dikurangi.',
            ]);
        }

        SlotParkir::whereIn('id', $idDihapus)->delete();
    }
}
