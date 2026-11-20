<?php

namespace Tests\Feature;

use App\Models\AreaParkir;
use App\Models\SlotParkir;
use App\Models\User;
use App\Services\LayananParkir;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AreaTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected $seeder = DatabaseSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_daftar_area_berisi_ringkasan_slot(): void
    {
        $this->getJson('/areas')
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonFragment(['nama' => 'Mobil Lantai 1', 'jenis' => 'mobil', 'lantai' => 1, 'total_slot' => 72, 'terisi' => 0, 'kosong' => 72]);
    }

    public function test_buat_area_baru_beserta_slotnya(): void
    {
        $this->postJson('/areas', ['nama' => 'Mobil Lantai 4', 'jenis' => 'mobil', 'lantai' => 4, 'jumlah_slot' => 36])
            ->assertCreated()
            ->assertJsonPath('data.total_slot', 36)
            ->assertJsonPath('data.lantai', 4);

        $this->assertTrue(SlotParkir::where('kode_slot', 'A4-36')->where('jenis', 'mobil')->exists());
    }

    public function test_buat_area_ditolak_jika_lantai_sudah_ada(): void
    {
        $this->postJson('/areas', ['nama' => 'Mobil Lantai 1 B', 'jenis' => 'mobil', 'lantai' => 1, 'jumlah_slot' => 72])
            ->assertStatus(422)
            ->assertJsonValidationErrors('lantai');
    }

    public function test_buat_area_ditolak_jika_jumlah_slot_bukan_kelipatan_zona(): void
    {
        $this->postJson('/areas', ['nama' => 'Motor Lantai 3', 'jenis' => 'motor', 'lantai' => 3, 'jumlah_slot' => 20])
            ->assertStatus(422)
            ->assertJsonValidationErrors('jumlah_slot');
    }

    public function test_ubah_area_mengganti_nama_dan_menambah_slot(): void
    {
        $area = AreaParkir::where('nama', 'Motor Lantai 2')->firstOrFail();

        $this->putJson("/areas/{$area->id}", ['nama' => 'Motor Lantai 2 (Timur)', 'jumlah_slot' => 90])
            ->assertOk()
            ->assertJsonPath('data.nama', 'Motor Lantai 2 (Timur)')
            ->assertJsonPath('data.total_slot', 90);

        $this->assertTrue(SlotParkir::where('kode_slot', 'M2-90')->exists());
    }

    public function test_slot_yang_pernah_dipakai_tidak_bisa_dikurangi(): void
    {
        $area = AreaParkir::where('nama', 'Motor Lantai 2')->firstOrFail();
        $this->putJson("/areas/{$area->id}", ['nama' => 'Motor Lantai 2', 'jumlah_slot' => 90])->assertOk();

        $slotTerakhir = SlotParkir::where('kode_slot', 'M2-90')->firstOrFail();
        app(LayananParkir::class)->checkIn($slotTerakhir->id, 'D 1 A', 'motor');

        $this->putJson("/areas/{$area->id}", ['nama' => 'Motor Lantai 2', 'jumlah_slot' => 72])
            ->assertStatus(422)
            ->assertJsonValidationErrors('jumlah_slot');

        $this->assertSame(90, $area->slot()->count());
    }

    public function test_denah_slot_menampilkan_posisi_status_dan_kendaraan(): void
    {
        $area = AreaParkir::where('nama', 'Mobil Lantai 1')->firstOrFail();
        $slot = SlotParkir::where('kode_slot', 'A1-22')->firstOrFail();
        app(LayananParkir::class)->checkIn($slot->id, 'B 1234 XY', 'mobil');

        $respons = $this->getJson("/areas/{$area->id}/slots")
            ->assertOk()
            ->assertJsonPath('data.area.terisi', 1)
            ->assertJsonCount(72, 'data.slot');

        // A1-22 = slot ke-22: zona 1 (indeks), blok 1, urutan 0.
        $a122 = collect($respons->json('data.slot'))->firstWhere('kode_slot', 'A1-22');
        $this->assertSame(['zona' => 1, 'blok' => 1, 'urutan' => 0], array_intersect_key($a122, array_flip(['zona', 'blok', 'urutan'])));
        $this->assertSame('terisi', $a122['status']);
        $this->assertSame('B1234XY', $a122['kendaraan']['plat']);
    }

    public function test_area_dengan_riwayat_parkir_tidak_bisa_dihapus(): void
    {
        $slot = SlotParkir::where('kode_slot', 'M1-01')->firstOrFail();
        app(LayananParkir::class)->checkIn($slot->id, 'D 2 B', 'motor');

        $this->deleteJson("/areas/{$slot->area_id}")->assertStatus(409);

        $this->assertTrue(AreaParkir::whereKey($slot->area_id)->exists());
    }

    public function test_area_tanpa_riwayat_parkir_bisa_dihapus_beserta_slotnya(): void
    {
        $kosong = $this->postJson('/areas', ['nama' => 'Motor Lantai 9', 'jenis' => 'motor', 'lantai' => 9, 'jumlah_slot' => 18])->json('data.id');

        $this->deleteJson("/areas/{$kosong}")->assertNoContent();

        $this->assertFalse(SlotParkir::where('kode_slot', 'M9-01')->exists());
    }
}
