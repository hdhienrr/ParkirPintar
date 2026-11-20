<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\SlotParkir;
use App\Models\Tarif;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingAksesTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected $seeder = DatabaseSeeder::class;

    private function bookingMilik(User $pemilik): Booking
    {
        return Booking::factory()
            ->for($pemilik)
            ->for(SlotParkir::where('kode_slot', 'A1-01')->firstOrFail(), 'slot')
            ->create();
    }

    public function test_tamu_yang_membuka_halaman_booking_diarahkan_ke_halaman_masuk(): void
    {
        $this->get('/booking/baru')->assertRedirect(route('login'));
    }

    public function test_user_bisa_membuka_halaman_booking_dan_booking_saya(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/booking/baru')->assertOk();
        $this->get('/booking-saya')->assertOk();
    }

    public function test_admin_tidak_bisa_membuat_booking(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get('/booking/baru')->assertForbidden();
    }

    public function test_pemilik_bisa_membuka_bukti_bookingnya(): void
    {
        $pemilik = User::factory()->create();
        $booking = $this->bookingMilik($pemilik);

        $this->actingAs($pemilik)->get("/booking/{$booking->kode_booking}")->assertOk()->assertSee($booking->kode_booking);
    }

    public function test_user_tidak_bisa_membuka_bukti_booking_orang_lain(): void
    {
        $booking = $this->bookingMilik(User::factory()->create());

        $this->actingAs(User::factory()->create())->get("/booking/{$booking->kode_booking}")->assertForbidden();
    }

    public function test_admin_bisa_membuka_bukti_booking_siapa_pun(): void
    {
        $booking = $this->bookingMilik(User::factory()->create());

        $this->actingAs(User::factory()->admin()->create())->get("/booking/{$booking->kode_booking}")->assertOk();
    }

    public function test_booking_saya_hanya_menampilkan_booking_milik_sendiri(): void
    {
        $saya = User::factory()->create();
        $milikSaya = $this->bookingMilik($saya);
        $milikOrangLain = Booking::factory()->for(User::factory())->create();

        $this->actingAs($saya)->get('/booking-saya')
            ->assertSee($milikSaya->kode_booking)
            ->assertDontSee($milikOrangLain->kode_booking);
    }

    public function test_halaman_peraturan_terbuka_tanpa_login(): void
    {
        $this->get('/peraturan')->assertOk();
    }

    public function test_angka_di_halaman_peraturan_diambil_dari_tabel_tarif(): void
    {
        Tarif::where('jenis', 'motor')->update(['tarif_jam_pertama' => 2500, 'denda_korban_per_jam' => 6000]);

        $this->get('/peraturan')
            ->assertSee('Rp2.500 jam pertama')
            ->assertSee('Rp6.000')
            ->assertSee('Rp100.000');
    }
}
