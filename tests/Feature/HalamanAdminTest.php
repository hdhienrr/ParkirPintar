<?php

namespace Tests\Feature;

use App\Enums\StatusBooking;
use App\Models\Booking;
use App\Models\SlotParkir;
use App\Models\User;
use App\Services\LayananKarcis;
use App\Services\LayananParkir;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HalamanAdminTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected $seeder = DatabaseSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-11-23 08:00'));
        $this->actingAs(User::factory()->admin()->create());
    }

    private function booking(string $kodeSlot, string $mulai, int $jam = 2): Booking
    {
        return Booking::factory()
            ->for(SlotParkir::where('kode_slot', $kodeSlot)->firstOrFail(), 'slot')
            ->jendela($mulai, $jam)
            ->create();
    }

    public function test_daftar_booking_menampilkan_booking_semua_akun_pada_tanggal_itu(): void
    {
        $pertama = $this->booking('A1-01', '2026-11-23 09:00');
        $kedua = $this->booking('M1-01', '2026-11-23 10:00');
        $besok = $this->booking('A1-02', '2026-11-24 09:00');

        $this->get('/admin/booking')
            ->assertOk()
            ->assertSee($pertama->kode_booking)
            ->assertSee($kedua->kode_booking)
            ->assertDontSee($besok->kode_booking);

        $this->get('/admin/booking?tanggal=2026-11-24')->assertSee($besok->kode_booking);
    }

    public function test_daftar_booking_bisa_disaring_per_status(): void
    {
        $dipesan = $this->booking('A1-01', '2026-11-23 09:00');
        $dibatalkan = $this->booking('A1-02', '2026-11-23 09:00');
        $dibatalkan->forceFill(['status' => StatusBooking::Dibatalkan])->save();

        $this->get('/admin/booking?status=dibatalkan')
            ->assertSee($dibatalkan->kode_booking)
            ->assertDontSee($dipesan->kode_booking);
    }

    public function test_booking_yang_melewati_waktu_diberi_label_merah(): void
    {
        $booking = $this->booking('A1-01', '2026-11-23 08:00', 1);
        app(LayananKarcis::class)->checkIn($booking->kode_booking);

        $this->travelTo(CarbonImmutable::parse('2026-11-23 09:20'));

        $this->get('/admin/booking')->assertSee('Melewati waktu');
        $this->get('/admin/booking/sedang-parkir')->assertSee('Melewati waktu');
    }

    public function test_booking_yang_belum_melewati_waktu_tanpa_label_merah(): void
    {
        $booking = $this->booking('A1-01', '2026-11-23 08:00', 2);
        app(LayananKarcis::class)->checkIn($booking->kode_booking);

        $this->get('/admin/booking')->assertDontSee('Melewati waktu');
    }

    public function test_daftar_kendaraan_menampilkan_kendaraan_yang_sedang_parkir(): void
    {
        $booking = $this->booking('A1-01', '2026-11-23 08:00');
        $kendaraan = app(LayananKarcis::class)->checkIn($booking->kode_booking)->kendaraan;

        $this->travelTo(CarbonImmutable::parse('2026-11-23 09:30'));

        $this->get('/admin/booking/sedang-parkir')
            ->assertOk()
            ->assertSee($kendaraan->nomor_karcis)
            ->assertSee($kendaraan->plat)
            ->assertSee('Rp8.000');
    }

    public function test_kendaraan_yang_sudah_keluar_tidak_ada_di_daftar(): void
    {
        $booking = $this->booking('A1-01', '2026-11-23 08:00');
        $kendaraan = app(LayananKarcis::class)->checkIn($booking->kode_booking)->kendaraan;
        app(LayananKarcis::class)->checkOut($kendaraan->id);

        $this->get('/admin/booking/sedang-parkir')->assertDontSee($kendaraan->nomor_karcis);
    }

    public function test_tab_sedang_parkir_memuat_kendaraan_dari_booking_hari_sebelumnya(): void
    {
        // Masuk kemarin dan belum keluar: tidak ada di daftar per tanggal hari ini, tetapi ada di tab sedang parkir.
        $this->travelTo(CarbonImmutable::parse('2026-11-22 20:00'));
        $kemarin = $this->booking('A1-01', '2026-11-22 20:00', 1);
        $karcis = app(LayananKarcis::class)->checkIn($kemarin->kode_booking)->kendaraan->nomor_karcis;

        $this->travelTo(CarbonImmutable::parse('2026-11-23 08:00'));

        $this->get('/admin/booking')->assertDontSee($kemarin->kode_booking);
        $this->get('/admin/booking/sedang-parkir')
            ->assertSee($kemarin->kode_booking)
            ->assertSee($karcis)
            ->assertSee('Melewati waktu');
    }

    public function test_tab_sedang_parkir_memuat_kendaraan_tanpa_booking(): void
    {
        app(LayananParkir::class)->checkIn(SlotParkir::where('kode_slot', 'A1-09')->value('id'), 'B 9 TB', 'mobil');

        $this->get('/admin/booking/sedang-parkir')
            ->assertSee('B9TB')
            ->assertSee('tanpa booking');
    }

    public function test_halaman_kendaraan_terpisah_sudah_tidak_ada(): void
    {
        $this->get('/admin/kendaraan')->assertNotFound();
    }

    public function test_user_tidak_bisa_membuka_halaman_admin_booking_dan_kendaraan(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/admin/booking')->assertForbidden();
        $this->get('/admin/booking/sedang-parkir')->assertForbidden();
    }
}
