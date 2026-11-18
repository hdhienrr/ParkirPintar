<?php

namespace Tests\Unit;

use App\Models\Tarif;
use App\Services\KalkulatorTarif;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class KalkulatorTarifTest extends TestCase
{
    private const TARIF = [
        'motor' => ['tarif_jam_pertama' => 2000, 'tarif_jam_berikutnya' => 1000, 'maks_harian' => 20000],
        'mobil' => ['tarif_jam_pertama' => 5000, 'tarif_jam_berikutnya' => 3000, 'maks_harian' => 50000],
    ];

    public static function kasusTarif(): array
    {
        return [
            'motor 15 menit gratis' => ['motor', 15, 0],
            'motor menit ke-16 kena jam pertama' => ['motor', 16, 2000],
            'motor tepat 1 jam' => ['motor', 60, 2000],
            'motor 61 menit dibulatkan 2 jam' => ['motor', 61, 3000],
            'motor 5 jam' => ['motor', 300, 6000],
            'motor 23 jam kena batas harian' => ['motor', 23 * 60, 20000],
            'mobil 15 menit gratis' => ['mobil', 15, 0],
            'mobil 2 jam 1 menit dibulatkan 3 jam' => ['mobil', 121, 11000],
            'mobil 16 jam tepat di batas harian' => ['mobil', 16 * 60, 50000],
            'mobil 20 jam kena batas harian' => ['mobil', 20 * 60, 50000],
            'mobil 25 jam = 1 hari + 1 jam' => ['mobil', 25 * 60, 55000],
        ];
    }

    #[DataProvider('kasusTarif')]
    public function test_biaya_sesuai_aturan_tarif(string $jenis, int $menit, int $biayaDiharapkan): void
    {
        $tarif = new Tarif(['jenis' => $jenis] + self::TARIF[$jenis]);
        $masuk = CarbonImmutable::parse('2026-11-18 08:00:00');

        $hasil = (new KalkulatorTarif)->hitung($tarif, $masuk, $masuk->addMinutes($menit));

        $this->assertSame($menit, $hasil['durasi_menit']);
        $this->assertSame($biayaDiharapkan, $hasil['biaya']);
    }

    public function test_pecahan_menit_dibulatkan_ke_atas(): void
    {
        $tarif = new Tarif(['jenis' => 'motor'] + self::TARIF['motor']);
        $masuk = CarbonImmutable::parse('2026-11-18 08:00:00');

        // 15 menit 1 detik sudah lewat masa gratis.
        $hasil = (new KalkulatorTarif)->hitung($tarif, $masuk, $masuk->addSeconds(15 * 60 + 1));

        $this->assertSame(16, $hasil['durasi_menit']);
        $this->assertSame(2000, $hasil['biaya']);
    }
}
