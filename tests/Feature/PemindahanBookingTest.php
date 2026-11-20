<?php

namespace Tests\Feature;

use App\Enums\StatusBooking;
use App\Models\Booking;
use App\Models\KendaraanParkir;
use App\Models\SlotParkir;
use App\Models\User;
use App\Services\LayananPemindahan;
use App\Services\PenyegarStatusBooking;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Pemindahan booking oleh admin, termasuk cabang overstay "dipindah ke slot bebas tanpa denda".
 * Hari uji: Senin, 23 November 2026.
 */
class PemindahanBookingTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected $seeder = DatabaseSeeder::class;

    private const HARI_INI = '2026-11-23';

    protected function setUp(): void
    {
        parent::setUp();

        $this->pukul('07:00');
        $this->actingAs(User::factory()->admin()->create());
    }

    private function pukul(string $jam): void
    {
        $this->travelTo(CarbonImmutable::parse(self::HARI_INI." {$jam}"));
    }

    private function slot(string $kode): SlotParkir
    {
        return SlotParkir::where('kode_slot', $kode)->firstOrFail();
    }

    private function booking(string $kodeSlot, string $jam, int $durasi = 2): Booking
    {
        return Booking::factory()
            ->for($this->slot($kodeSlot), 'slot')
            ->jendela(self::HARI_INI." {$jam}", $durasi)
            ->create();
    }

    private function pindah(Booking $booking, string $kodeSlot): TestResponse
    {
        return $this->postJson("/admin/booking/{$booking->kode_booking}/pindah", ['slot_id' => $this->slot($kodeSlot)->id]);
    }

    /** Penghalang 08.00–10.00 masuk lalu melewati waktu; berikutnya 11.00–13.00 tiba dan menunggu. */
    private function berikutnyaMenunggu(): array
    {
        $penghalang = $this->booking('A1-01', '08:00');
        $berikutnya = $this->booking('A1-01', '11:00');

        $this->pukul('08:00');
        $this->postJson('/checkin', ['kode_booking' => $penghalang->kode_booking])->assertCreated();
        $this->pukul('11:00');
        $this->postJson('/checkin', ['kode_booking' => $berikutnya->kode_booking])->assertStatus(202);

        return [$penghalang, $berikutnya];
    }

    public function test_booking_yang_menunggu_dipindah_ke_slot_bebas_langsung_check_in_tanpa_denda(): void
    {
        [$penghalang, $berikutnya] = $this->berikutnyaMenunggu();

        $this->pukul('11:05');
        $this->pindah($berikutnya, 'A1-02')
            ->assertOk()
            ->assertJsonPath('data.status', 'aktif')
            ->assertJsonPath('data.kode_slot', 'A1-02');

        $berikutnya->refresh();
        $this->assertSame(StatusBooking::Aktif, $berikutnya->status);
        $this->assertSame(0, $berikutnya->denda);
        $this->assertNotNull($berikutnya->kendaraan->nomor_karcis);

        // Setelah batas tunggu lewat pun tidak ada pembatalan atau denda untuk siapa pun.
        $this->pukul('11:30');
        app(PenyegarStatusBooking::class)->segarkan();
        $this->assertNull($penghalang->refresh()->denda_mulai);

        $idPenghalang = KendaraanParkir::where('booking_id', $penghalang->id)->value('id');
        $this->postJson("/checkout/{$idPenghalang}")->assertOk()->assertJsonPath('data.denda', 0);
    }

    public function test_booking_yang_masih_dipesan_hanya_berganti_slot(): void
    {
        $booking = $this->booking('A1-01', '10:00');

        $this->pindah($booking, 'A1-05')->assertOk()->assertJsonPath('data.status', 'dipesan');

        $this->assertSame($this->slot('A1-05')->id, $booking->refresh()->slot_id);
        $this->assertNull($booking->kendaraan);
    }

    public function test_pindah_ke_slot_yang_sudah_dibooking_pada_jam_itu_ditolak(): void
    {
        $booking = $this->booking('A1-01', '10:00');
        $this->booking('A1-02', '11:00');

        $this->pindah($booking, 'A1-02')->assertStatus(409);

        $this->assertSame($this->slot('A1-01')->id, $booking->refresh()->slot_id);
    }

    public function test_pindah_ke_slot_yang_sedang_terisi_ditolak(): void
    {
        [, $berikutnya] = $this->berikutnyaMenunggu();
        $lain = $this->booking('A1-03', '11:00');
        $this->postJson('/checkin', ['kode_booking' => $lain->kode_booking])->assertCreated();

        $this->pindah($berikutnya, 'A1-03')->assertStatus(409);

        $this->assertSame(StatusBooking::Menunggu, $berikutnya->refresh()->status);
    }

    public function test_pindah_ke_slot_jenis_lain_ditolak(): void
    {
        $booking = $this->booking('A1-01', '10:00');

        $this->pindah($booking, 'M1-01')->assertStatus(422);
    }

    public function test_booking_yang_sudah_selesai_tidak_bisa_dipindah(): void
    {
        $booking = $this->booking('A1-01', '10:00');
        $booking->forceFill(['status' => StatusBooking::Selesai])->save();

        $this->pindah($booking, 'A1-02')->assertStatus(409);
    }

    public function test_slot_pengganti_mendahulukan_lantai_yang_sama(): void
    {
        $booking = $this->booking('A2-10', '10:00');

        $pengganti = app(LayananPemindahan::class)->slotPengganti($booking);

        $this->assertSame($this->slot('A2-10')->area_id, $pengganti->first()->area_id);
        $this->assertNotContains('A2-10', $pengganti->pluck('kode_slot'));
        $this->assertTrue($pengganti->every(fn (SlotParkir $s) => $s->jenis === 'mobil'));
    }

    public function test_slot_pengganti_tidak_memuat_slot_yang_bentrok_atau_terisi(): void
    {
        [, $berikutnya] = $this->berikutnyaMenunggu();
        $this->booking('A1-02', '12:00');

        $kode = app(LayananPemindahan::class)->slotPengganti($berikutnya)->pluck('kode_slot');

        $this->assertNotContains('A1-02', $kode);
        $this->assertContains('A1-03', $kode);
    }

    public function test_halaman_pindah_menampilkan_pilihan_slot(): void
    {
        $booking = $this->booking('A1-01', '10:00');

        $this->get("/admin/booking/{$booking->kode_booking}/pindah")
            ->assertOk()
            ->assertSee('A1-02')
            ->assertDontSee('<option value="'.$this->slot('A1-01')->id.'">', false);
    }

    public function test_user_tidak_bisa_memindahkan_booking(): void
    {
        $booking = $this->booking('A1-01', '10:00');

        $this->actingAs(User::factory()->create())
            ->post("/admin/booking/{$booking->kode_booking}/pindah", ['slot_id' => $this->slot('A1-02')->id])
            ->assertForbidden();
    }
}
