<?php

namespace Tests\Unit;

use App\Models\Tarif;
use App\Services\KalkulatorDenda;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class KalkulatorDendaTest extends TestCase
{
    private const TARIF = [
        'motor' => ['denda_pelaku_per_jam' => 10000, 'denda_pelaku_maks_harian' => 50000, 'denda_korban_per_jam' => 5000, 'denda_korban_maks_harian' => 25000],
        'mobil' => ['denda_pelaku_per_jam' => 20000, 'denda_pelaku_maks_harian' => 100000, 'denda_korban_per_jam' => 10000, 'denda_korban_maks_harian' => 50000],
    ];

    private function denda(string $peran, string $jenis, int $menit): int
    {
        $tarif = new Tarif(['jenis' => $jenis] + self::TARIF[$jenis]);
        $mulai = CarbonImmutable::parse('2026-11-23 11:15:00');

        return (new KalkulatorDenda)->{$peran}($tarif, $mulai, $mulai->addMinutes($menit));
    }

    public static function kasusDenda(): array
    {
        return [
            'pelaku motor 1 menit dibulatkan 1 jam' => ['pelaku', 'motor', 1, 10000],
            'pelaku motor tepat 1 jam' => ['pelaku', 'motor', 60, 10000],
            'pelaku motor 61 menit dibulatkan 2 jam' => ['pelaku', 'motor', 61, 20000],
            'pelaku motor 8 jam kena batas harian' => ['pelaku', 'motor', 8 * 60, 50000],
            'pelaku motor 25 jam = batas 1 blok + 1 jam' => ['pelaku', 'motor', 25 * 60, 60000],
            'pelaku motor 48 jam = 2 blok penuh' => ['pelaku', 'motor', 48 * 60, 100000],
            'pelaku mobil 3 jam' => ['pelaku', 'mobil', 3 * 60, 60000],
            'korban mobil 3 jam' => ['korban', 'mobil', 3 * 60, 30000],
            'korban mobil 6 jam kena batas korban' => ['korban', 'mobil', 6 * 60, 50000],
            'korban motor 30 menit' => ['korban', 'motor', 30, 5000],
        ];
    }

    #[DataProvider('kasusDenda')]
    public function test_denda_per_jam_dengan_batas_per_blok_24_jam(string $peran, string $jenis, int $menit, int $harapan): void
    {
        $this->assertSame($harapan, $this->denda($peran, $jenis, $menit));
    }

    public function test_tidak_ada_denda_bila_keluar_sebelum_denda_mulai(): void
    {
        $this->assertSame(0, $this->denda('korban', 'mobil', -5));
        $this->assertSame(0, $this->denda('korban', 'mobil', 0));
    }
}
