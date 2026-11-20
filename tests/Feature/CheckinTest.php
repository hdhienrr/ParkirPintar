<?php

namespace Tests\Feature;

use App\Models\KendaraanParkir;
use App\Models\SlotParkir;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckinTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected $seeder = DatabaseSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->admin()->create());
    }

    private function slot(string $kode): SlotParkir
    {
        return SlotParkir::where('kode_slot', $kode)->firstOrFail();
    }

    private function checkin(string $kode, string $plat, string $jenis = 'mobil')
    {
        return $this->postJson('/checkin', [
            'slot_id' => $this->slot($kode)->id,
            'plat' => $plat,
            'jenis' => $jenis,
        ]);
    }

    public function test_plat_yang_masih_di_dalam_ditolak(): void
    {
        $this->checkin('A1-01', 'B 1234 XY')->assertCreated()->assertJsonPath('data.plat', 'B1234XY');

        // Plat sama dengan penulisan berbeda, di slot lain.
        $this->checkin('A1-02', 'b1234xy')->assertStatus(409);

        $this->assertSame(1, KendaraanParkir::aktif()->count());
    }

    public function test_checkout_menghitung_durasi_dan_biaya(): void
    {
        $id = $this->checkin('A1-01', 'B 1234 XY')->json('data.id');

        $this->travel(90)->minutes();

        $this->postJson("/checkout/{$id}")
            ->assertOk()
            ->assertJsonPath('data.durasi_menit', 90)
            ->assertJsonPath('data.biaya', 8000);
    }

    public function test_plat_boleh_masuk_lagi_setelah_checkout_sebagai_transaksi_baru(): void
    {
        $id = $this->checkin('A1-01', 'B 1234 XY')->json('data.id');
        $this->postJson("/checkout/{$id}")->assertOk();

        $this->checkin('A1-02', 'B 1234 XY')->assertCreated();

        $this->assertSame(2, KendaraanParkir::where('plat', 'B1234XY')->count());
    }

    public function test_slot_yang_terisi_ditolak(): void
    {
        $this->checkin('A1-01', 'B 1111 AA')->assertCreated();

        $this->checkin('A1-01', 'B 2222 BB')
            ->assertStatus(409)
            ->assertJsonPath('message', 'Slot A1-01 sudah terisi. Pilih slot lain.');
    }

    public function test_jenis_kendaraan_tidak_sesuai_slot_ditolak(): void
    {
        $this->checkin('A1-01', 'B 3333 CC', 'motor')->assertStatus(422);

        $this->assertSame(0, KendaraanParkir::count());
    }

    public function test_area_penuh_ditolak(): void
    {
        $area = $this->slot('M1-01')->area;

        $area->slot->each(fn (SlotParkir $slot) => KendaraanParkir::factory()->for($slot, 'slot')->create());

        $this->checkin('M1-01', 'D 9999 ZZ', 'motor')
            ->assertStatus(409)
            ->assertJsonPath('message', 'Motor Lantai 1 sudah penuh.');
    }

    public function test_checkout_dua_kali_ditolak(): void
    {
        $id = $this->checkin('M2-05', 'D 1 A', 'motor')->json('data.id');

        $this->postJson("/checkout/{$id}")->assertOk()->assertJsonPath('data.biaya', 0);
        $this->postJson("/checkout/{$id}")->assertStatus(409);
    }
}
