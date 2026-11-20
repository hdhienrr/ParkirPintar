<?php

namespace App\Services;

use App\Enums\JenisKendaraan;
use App\Models\KendaraanParkir;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;

class LayananLaporan
{
    public function __construct(private readonly LayananArea $layananArea) {}

    /**
     * Slot terisi dan kosong per area, per jenis kendaraan, dan totalnya.
     */
    public function okupansi(): array
    {
        $area = $this->layananArea->daftarRingkasan();

        return [
            'area' => $area->values(),
            'per_jenis' => $area->groupBy('jenis')->map(fn (Collection $baris) => $this->jumlahOkupansi($baris)),
            'total' => $this->jumlahOkupansi($area),
        ];
    }

    /**
     * Pendapatan per hari check-out, dipisah per jenis kendaraan.
     */
    public function pendapatan(CarbonImmutable $dari, CarbonImmutable $sampai): array
    {
        $harian = $this->queryPendapatanHarian($dari, $sampai)
            ->get()
            ->map(fn ($baris) => [
                'tanggal' => (string) $baris->tanggal,
                'transaksi' => (int) $baris->transaksi,
                ...$this->angkaPerJenis(fn (string $jenis) => (int) $baris->{$jenis}),
                'pendapatan' => (int) $baris->pendapatan,
            ]);

        return [
            'dari' => $dari->toDateString(),
            'sampai' => $sampai->toDateString(),
            'harian' => $harian,
            'total' => [
                'transaksi' => $harian->sum('transaksi'),
                ...$this->angkaPerJenis(fn (string $jenis) => $harian->sum($jenis)),
                'pendapatan' => $harian->sum('pendapatan'),
            ],
        ];
    }

    private function jumlahOkupansi(Collection $area): array
    {
        return [
            'total_slot' => $area->sum('total_slot'),
            'terisi' => $area->sum('terisi'),
            'kosong' => $area->sum('kosong'),
        ];
    }

    private function queryPendapatanHarian(CarbonImmutable $dari, CarbonImmutable $sampai): Builder
    {
        $query = KendaraanParkir::query()
            ->whereNotNull('waktu_keluar')
            ->whereBetween('waktu_keluar', [$dari->startOfDay(), $sampai->endOfDay()])
            ->selectRaw('DATE(waktu_keluar) AS tanggal')
            ->selectRaw('COUNT(*) AS transaksi');

        // Alias kolom diambil dari nilai enum (mobil, motor), bukan dari input pengguna.
        foreach (JenisKendaraan::nilai() as $jenis) {
            $query->selectRaw("SUM(CASE WHEN jenis = ? THEN biaya ELSE 0 END) AS {$jenis}", [$jenis]);
        }

        return $query
            ->selectRaw('SUM(biaya) AS pendapatan')
            ->groupByRaw('DATE(waktu_keluar)')
            ->orderBy('tanggal')
            ->toBase();
    }

    /**
     * @param  callable(string): int  $hitung
     * @return array<string, int>
     */
    private function angkaPerJenis(callable $hitung): array
    {
        $hasil = [];
        foreach (JenisKendaraan::nilai() as $jenis) {
            $hasil[$jenis] = $hitung($jenis);
        }

        return $hasil;
    }
}
