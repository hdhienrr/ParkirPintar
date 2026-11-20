<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\KendaraanParkir;
use App\Models\SlotParkir;
use App\Models\User;
use App\Services\LayananKarcis;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KarcisAksesTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected $seeder = DatabaseSeeder::class;

    private User $pemilik;

    private KendaraanParkir $kendaraan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-11-23 08:00'));
        $this->pemilik = User::factory()->create();

        $booking = Booking::factory()
            ->for($this->pemilik)
            ->for(SlotParkir::where('kode_slot', 'A1-01')->firstOrFail(), 'slot')
            ->jendela('2026-11-23 08:00', 2)
            ->create();

        $this->kendaraan = app(LayananKarcis::class)->checkIn($booking->kode_booking)->kendaraan;
    }

    private function urlKarcis(): string
    {
        return "/karcis/{$this->kendaraan->nomor_karcis}";
    }

    public function test_pemilik_bisa_membuka_karcisnya(): void
    {
        $this->actingAs($this->pemilik)->get($this->urlKarcis())
            ->assertOk()
            ->assertSee($this->kendaraan->nomor_karcis)
            ->assertSee('Karcis parkir');
    }

    public function test_user_lain_tidak_bisa_membuka_karcis(): void
    {
        $this->actingAs(User::factory()->create())->get($this->urlKarcis())->assertForbidden();
    }

    public function test_admin_bisa_membuka_karcis_siapa_pun(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get($this->urlKarcis())->assertOk();
    }

    public function test_tamu_diarahkan_ke_halaman_masuk(): void
    {
        $this->get($this->urlKarcis())->assertRedirect(route('login'));
    }

    public function test_struk_keluar_tampil_setelah_check_out(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-23 09:30'));
        app(LayananKarcis::class)->checkOut($this->kendaraan->id);

        $this->actingAs($this->pemilik)->get($this->urlKarcis())
            ->assertSee('Struk keluar')
            ->assertSee('Rp8.000');
    }

    public function test_user_tidak_bisa_tandai_keluar(): void
    {
        $this->actingAs($this->pemilik)
            ->post("/admin/booking/{$this->kendaraan->booking->kode_booking}/keluar")
            ->assertForbidden();
    }
}
