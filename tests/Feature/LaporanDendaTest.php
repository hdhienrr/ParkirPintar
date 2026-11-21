<?php

namespace Tests\Feature;

use App\Enums\StatusBooking;
use App\Models\Booking;
use App\Models\SlotParkir;
use App\Models\User;
use App\Services\LayananKarcis;
use App\Services\PenyegarStatusBooking;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Denda tampil di laporan pendapatan sebagai baris terpisah, dan tidak dijumlahkan ke pendapatan parkir.
 *
 * Skenario 23 November 2026: penghalang (08.00–10.00) melewati waktu, booking berikutnya (11.00–13.00)
 * dibatalkan sistem pukul 11.15 dan ditandai keluar pukul 12.40 (denda korban Rp20.000),
 * lalu penghalang check-out pukul 13.20 (biaya parkir Rp20.000, denda pelaku Rp60.000).
 */
class LaporanDendaTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected $seeder = DatabaseSeeder::class;

    private const HARI_INI = '2026-11-23';

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->admin()->create());
        $this->buatSkenarioDenda();
    }

    private function pukul(string $jam): void
    {
        $this->travelTo(CarbonImmutable::parse(self::HARI_INI." {$jam}"));
    }

    private function buatSkenarioDenda(): void
    {
        $slot = SlotParkir::where('kode_slot', 'A1-01')->firstOrFail();
        $penghalang = Booking::factory()->for($slot, 'slot')->jendela(self::HARI_INI.' 08:00', 2)->create();
        $berikutnya = Booking::factory()->for($slot, 'slot')->jendela(self::HARI_INI.' 11:00', 2)->create();
        $karcis = app(LayananKarcis::class);

        $this->pukul('08:00');
        $kendaraan = $karcis->checkIn($penghalang->kode_booking)->kendaraan;

        $this->pukul('11:00');
        $karcis->checkIn($berikutnya->kode_booking);

        $this->pukul('11:15');
        app(PenyegarStatusBooking::class)->segarkan();

        $this->pukul('12:40');
        $karcis->tandaiKeluar($berikutnya);

        $this->pukul('13:20');
        $karcis->checkOut($kendaraan->id);
    }

    public function test_pendapatan_parkir_tidak_termasuk_denda(): void
    {
        $this->getJson('/reports/revenue?dari='.self::HARI_INI.'&sampai='.self::HARI_INI)
            ->assertOk()
            ->assertJsonPath('data.harian', [
                ['tanggal' => self::HARI_INI, 'transaksi' => 1, 'mobil' => 20000, 'motor' => 0, 'pendapatan' => 20000],
            ])
            ->assertJsonPath('data.total.pendapatan', 20000);
    }

    public function test_denda_dilaporkan_terpisah_per_hari(): void
    {
        $this->getJson('/reports/revenue?dari='.self::HARI_INI.'&sampai='.self::HARI_INI)
            ->assertJsonPath('data.denda.harian', [
                ['tanggal' => self::HARI_INI, 'pelaku' => 60000, 'korban' => 20000, 'jumlah' => 80000],
            ])
            ->assertJsonPath('data.denda.total', 80000);
    }

    public function test_denda_di_luar_rentang_tanggal_tidak_dihitung(): void
    {
        $this->getJson('/reports/revenue?dari=2026-11-24&sampai=2026-11-24')
            ->assertJsonPath('data.denda.harian', [])
            ->assertJsonPath('data.denda.total', 0);
    }

    public function test_baris_denda_tetap_tampil_saat_tidak_ada_pendapatan_parkir(): void
    {
        // 25 November: tidak ada kendaraan check-out, hanya satu booking dibatalkan sistem yang keluar terlambat.
        Booking::factory()
            ->for(SlotParkir::where('kode_slot', 'A1-05')->firstOrFail(), 'slot')
            ->jendela('2026-11-25 09:00', 2)
            ->create()
            ->forceFill(['status' => StatusBooking::DibatalkanSistem, 'waktu_keluar' => '2026-11-25 10:00', 'denda' => 5000])
            ->save();

        $this->get('/reports/revenue?dari=2026-11-25&sampai=2026-11-25')
            ->assertOk()
            ->assertSee('Belum ada kendaraan yang check-out')
            ->assertSee('Denda melewati waktu (terpisah dari pendapatan parkir)')
            ->assertSee('Rp5.000');
    }

    public function test_halaman_laporan_menampilkan_denda_sebagai_baris_terpisah(): void
    {
        $this->get('/reports/revenue?dari='.self::HARI_INI.'&sampai='.self::HARI_INI)
            ->assertOk()
            ->assertSee('Pendapatan parkir')
            ->assertSee('Denda melewati waktu (terpisah dari pendapatan parkir)')
            ->assertSee('Denda per hari')
            ->assertSee('Rp80.000');
    }
}
