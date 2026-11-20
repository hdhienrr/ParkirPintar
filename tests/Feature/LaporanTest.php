<?php

namespace Tests\Feature;

use App\Models\SlotParkir;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaporanTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected $seeder = DatabaseSeeder::class;

    private function parkir(string $kode, string $plat, string $jenis, int $menit): void
    {
        $id = $this->postJson('/checkin', [
            'slot_id' => SlotParkir::where('kode_slot', $kode)->value('id'),
            'plat' => $plat,
            'jenis' => $jenis,
        ])->json('data.id');

        $this->travel($menit)->minutes();
        $this->postJson("/checkout/{$id}")->assertOk();
    }

    public function test_okupansi_per_area_dan_per_jenis(): void
    {
        $this->postJson('/checkin', ['slot_id' => SlotParkir::where('kode_slot', 'A1-01')->value('id'), 'plat' => 'B 1 A', 'jenis' => 'mobil']);
        $this->postJson('/checkin', ['slot_id' => SlotParkir::where('kode_slot', 'M2-01')->value('id'), 'plat' => 'B 2 A', 'jenis' => 'motor']);

        $this->getJson('/reports/occupancy')
            ->assertOk()
            ->assertJsonPath('data.total', ['total_slot' => 360, 'terisi' => 2, 'kosong' => 358])
            ->assertJsonPath('data.per_jenis.mobil.terisi', 1)
            ->assertJsonPath('data.per_jenis.motor.total_slot', 144)
            ->assertJsonFragment(['nama' => 'Motor Lantai 2', 'terisi' => 1, 'kosong' => 71]);
    }

    public function test_pendapatan_dijumlahkan_per_hari_check_out(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 17)->setTime(8, 0));
        $this->parkir('A1-01', 'B 1 A', 'mobil', 90);  // 2 jam = 8.000
        $this->parkir('M1-01', 'B 2 B', 'motor', 10);  // gratis

        $this->travelTo(now()->setDate(2026, 11, 18)->setTime(9, 0));
        $this->parkir('M1-02', 'B 3 C', 'motor', 61);  // 2 jam = 3.000

        $this->getJson('/reports/revenue?dari=2026-11-17&sampai=2026-11-18')
            ->assertOk()
            ->assertJsonPath('data.harian', [
                ['tanggal' => '2026-11-17', 'transaksi' => 2, 'mobil' => 8000, 'motor' => 0, 'pendapatan' => 8000],
                ['tanggal' => '2026-11-18', 'transaksi' => 1, 'mobil' => 0, 'motor' => 3000, 'pendapatan' => 3000],
            ])
            ->assertJsonPath('data.total.pendapatan', 11000);
    }

    public function test_semua_halaman_bisa_dibuka(): void
    {
        foreach (['/', '/parkir/mobil', '/parkir/motor', '/areas', '/reports/occupancy', '/reports/revenue'] as $halaman) {
            $this->get($halaman)->assertOk();
        }
    }
}
