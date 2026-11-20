<?php

namespace Tests\Feature;

use App\Enums\StatusBooking;
use App\Models\Booking;
use App\Models\KendaraanParkir;
use App\Models\SlotParkir;
use App\Models\User;
use App\Services\LayananParkir;
use App\Services\PenyegarStatusBooking;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Check-in dengan kode booking, karcis, check-out, overstay, denda, dan tandai keluar.
 * Hari uji: Senin, 23 November 2026. Admin yang melakukan semua aksi.
 *
 * Cabang "booking berikutnya dipindah ke slot lain tanpa denda" diuji di langkah 5,
 * karena pemindahan booking memang belum ada di langkah ini.
 */
class CheckinBookingTest extends TestCase
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

    private function pukul(string $jam, string $tanggal = self::HARI_INI): void
    {
        $this->travelTo(CarbonImmutable::parse("{$tanggal} {$jam}"));
    }

    private function booking(string $kodeSlot, string $mulai, int $jam = 2, array $ubah = []): Booking
    {
        return Booking::factory()
            ->for(SlotParkir::where('kode_slot', $kodeSlot)->firstOrFail(), 'slot')
            ->jendela($mulai, $jam)
            ->create($ubah);
    }

    private function checkin(Booking $booking): TestResponse
    {
        return $this->postJson('/checkin', ['kode_booking' => $booking->kode_booking]);
    }

    /** Penghalang 08.00–10.00 sudah masuk, berikutnya 11.00–13.00 di slot yang sama. */
    private function penghalangDanBerikutnya(): array
    {
        $penghalang = $this->booking('A1-01', self::HARI_INI.' 08:00');
        $berikutnya = $this->booking('A1-01', self::HARI_INI.' 11:00');

        $this->pukul('08:00');
        $this->checkin($penghalang)->assertCreated();

        return [$penghalang, $berikutnya];
    }

    // ---------- Check-in ----------

    public function test_check_in_15_menit_sebelum_mulai_diterima_dan_karcis_terbit(): void
    {
        $booking = $this->booking('A1-01', self::HARI_INI.' 08:00');
        $this->pukul('07:45');

        $this->checkin($booking)
            ->assertCreated()
            ->assertJsonPath('data.nomor_karcis', 'PP-20261123-0001')
            ->assertJsonPath('data.kode_booking', $booking->kode_booking)
            ->assertJsonPath('data.kode_slot', 'A1-01');

        $this->assertSame(StatusBooking::Aktif, $booking->refresh()->status);
        $this->assertSame($booking->id, KendaraanParkir::where('nomor_karcis', 'PP-20261123-0001')->value('booking_id'));
    }

    public function test_check_in_lebih_awal_dari_15_menit_ditolak(): void
    {
        $booking = $this->booking('A1-01', self::HARI_INI.' 08:00');
        $this->pukul('07:44');

        $this->checkin($booking)->assertStatus(422);

        $this->assertSame(StatusBooking::Dipesan, $booking->refresh()->status);
    }

    public function test_check_in_terlambat_sampai_30_menit_masih_diterima(): void
    {
        $booking = $this->booking('A1-01', self::HARI_INI.' 08:00');
        $this->pukul('08:30');

        $this->checkin($booking)->assertCreated();
    }

    public function test_lewat_30_menit_tanpa_mencoba_check_in_menjadi_hangus_tanpa_denda(): void
    {
        $booking = $this->booking('A1-01', self::HARI_INI.' 08:00');
        $this->pukul('08:31');

        $this->checkin($booking)->assertStatus(409);

        $booking->refresh();
        $this->assertSame(StatusBooking::Hangus, $booking->status);
        $this->assertSame(0, $booking->denda);
    }

    public function test_check_in_dengan_kode_yang_tidak_ada_ditolak(): void
    {
        $this->postJson('/checkin', ['kode_booking' => 'BK-TIDAKADA'])->assertNotFound();
    }

    public function test_check_in_tanpa_booking_ditolak(): void
    {
        $slot = SlotParkir::where('kode_slot', 'A1-01')->firstOrFail();

        $this->postJson('/checkin', ['slot_id' => $slot->id, 'plat' => 'B 1 A', 'jenis' => 'mobil'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('kode_booking');

        $this->assertSame(0, KendaraanParkir::count());
    }

    public function test_check_in_ditolak_jika_plat_masih_tercatat_di_dalam(): void
    {
        app(LayananParkir::class)->checkIn(SlotParkir::where('kode_slot', 'A1-05')->value('id'), 'B 1234 XY', 'mobil');
        $booking = $this->booking('A1-01', self::HARI_INI.' 08:00', 2, ['plat' => 'B1234XY']);
        $this->pukul('08:00');

        $this->checkin($booking)->assertStatus(409);

        $this->assertSame(StatusBooking::Dipesan, $booking->refresh()->status);
    }

    public function test_nomor_karcis_urut_per_hari(): void
    {
        $pertama = $this->booking('A1-01', self::HARI_INI.' 08:00');
        $kedua = $this->booking('A1-02', self::HARI_INI.' 08:00');
        $besok = $this->booking('A1-03', '2026-11-24 08:00');

        $this->pukul('08:00');
        $this->checkin($pertama)->assertJsonPath('data.nomor_karcis', 'PP-20261123-0001');
        $this->checkin($kedua)->assertJsonPath('data.nomor_karcis', 'PP-20261123-0002');

        $this->pukul('08:00', '2026-11-24');
        $this->checkin($besok)->assertJsonPath('data.nomor_karcis', 'PP-20261124-0001');
    }

    // ---------- Check-out ----------

    public function test_check_out_menutup_booking_dan_biaya_dihitung_dari_waktu_nyata(): void
    {
        $booking = $this->booking('A1-01', self::HARI_INI.' 08:00');
        $this->pukul('08:00');
        $id = $this->checkin($booking)->json('data.id');

        // Booking sampai 10.00, tetapi keluar 11.30: 3 jam 30 menit dibulatkan 4 jam.
        $this->pukul('11:30');
        $this->postJson("/checkout/{$id}")
            ->assertOk()
            ->assertJsonPath('data.durasi_menit', 210)
            ->assertJsonPath('data.biaya', 14000)
            ->assertJsonPath('data.denda', 0)
            ->assertJsonPath('data.total', 14000);

        $this->assertSame(StatusBooking::Selesai, $booking->refresh()->status);
    }

    // ---------- Overstay ----------

    public function test_booking_berikutnya_menunggu_jika_slot_masih_ditempati(): void
    {
        [, $berikutnya] = $this->penghalangDanBerikutnya();
        $this->pukul('11:00');

        $this->checkin($berikutnya)->assertStatus(202)->assertJsonPath('data.status', 'menunggu');

        $berikutnya->refresh();
        $this->assertSame(StatusBooking::Menunggu, $berikutnya->status);
        $this->assertSame(self::HARI_INI.' 11:00:00', $berikutnya->menunggu_sejak->toDateTimeString());
    }

    public function test_hitungan_menunggu_dimulai_dari_waktu_mulai_bila_datang_lebih_awal(): void
    {
        [, $berikutnya] = $this->penghalangDanBerikutnya();

        $this->pukul('10:50');
        $this->checkin($berikutnya)->assertStatus(202);
        $this->assertSame(self::HARI_INI.' 11:00:00', $berikutnya->refresh()->menunggu_sejak->toDateTimeString());

        // 25 menit setelah datang, tetapi baru 10 menit dari jadwal mulai: belum dibatalkan.
        $this->pukul('11:10');
        app(PenyegarStatusBooking::class)->segarkan();
        $this->assertSame(StatusBooking::Menunggu, $berikutnya->refresh()->status);
    }

    public function test_penghalang_keluar_dalam_15_menit_booking_berikutnya_langsung_check_in_tanpa_denda(): void
    {
        [$penghalang, $berikutnya] = $this->penghalangDanBerikutnya();
        $this->pukul('11:00');
        $this->checkin($berikutnya)->assertStatus(202);

        $this->pukul('11:10');
        $idPenghalang = KendaraanParkir::where('booking_id', $penghalang->id)->value('id');
        $this->postJson("/checkout/{$idPenghalang}")
            ->assertOk()
            ->assertJsonPath('data.denda', 0)
            ->assertJsonPath('masuk_menyusul.kode_booking', $berikutnya->kode_booking);

        $this->assertSame(StatusBooking::Aktif, $berikutnya->refresh()->status);
        $this->assertSame(0, $berikutnya->denda);
        $this->assertNotNull($berikutnya->kendaraan->nomor_karcis);
    }

    public function test_penghalang_belum_keluar_setelah_15_menit_booking_berikutnya_dibatalkan_sistem(): void
    {
        [$penghalang, $berikutnya] = $this->penghalangDanBerikutnya();
        $this->pukul('11:00');
        $this->checkin($berikutnya)->assertStatus(202);

        $this->pukul('11:16');
        app(PenyegarStatusBooking::class)->segarkan();

        $berikutnya->refresh();
        $this->assertSame(StatusBooking::DibatalkanSistem, $berikutnya->status);
        $this->assertNotEmpty($berikutnya->alasan);
        $this->assertSame($penghalang->id, $berikutnya->penyebab_booking_id);
        $this->assertSame(0, $berikutnya->denda);
        $this->assertSame(self::HARI_INI.' 11:15:00', $penghalang->refresh()->denda_mulai->toDateTimeString());
    }

    public function test_denda_pelaku_dihitung_per_jam_dari_denda_mulai_saat_check_out(): void
    {
        [$penghalang, $berikutnya] = $this->penghalangDanBerikutnya();
        $this->pukul('11:00');
        $this->checkin($berikutnya);

        // Keluar 13.20: denda dari 11.15 = 2 jam 5 menit, dibulatkan 3 jam x Rp20.000.
        $this->pukul('13:20');
        $idPenghalang = KendaraanParkir::where('booking_id', $penghalang->id)->value('id');
        $this->postJson("/checkout/{$idPenghalang}")
            ->assertOk()
            ->assertJsonPath('data.biaya', 20000)
            ->assertJsonPath('data.denda', 60000)
            ->assertJsonPath('data.total', 80000);

        $this->assertSame(60000, $penghalang->refresh()->denda);
    }

    // ---------- Tandai keluar ----------

    private function dibatalkanSistem(): Booking
    {
        [, $berikutnya] = $this->penghalangDanBerikutnya();
        $this->pukul('11:00');
        $this->checkin($berikutnya);
        $this->pukul('11:15');
        app(PenyegarStatusBooking::class)->segarkan();

        return $berikutnya->refresh();
    }

    public function test_tandai_keluar_dalam_15_menit_setelah_dibatalkan_tanpa_biaya(): void
    {
        $booking = $this->dibatalkanSistem();

        $this->pukul('11:25');
        $this->postJson("/admin/booking/{$booking->kode_booking}/keluar")
            ->assertOk()
            ->assertJsonPath('data.denda', 0);

        $this->assertSame(self::HARI_INI.' 11:25:00', $booking->refresh()->waktu_keluar->toDateTimeString());
    }

    public function test_tandai_keluar_setelah_15_menit_kena_denda_korban_per_jam(): void
    {
        $booking = $this->dibatalkanSistem();

        // Denda korban mulai 11.30. Keluar 12.40 = 1 jam 10 menit, dibulatkan 2 jam x Rp10.000.
        $this->pukul('12:40');
        $this->postJson("/admin/booking/{$booking->kode_booking}/keluar")->assertJsonPath('data.denda', 20000);
    }

    public function test_tandai_keluar_hanya_untuk_booking_yang_dibatalkan_sistem(): void
    {
        $booking = $this->booking('A1-01', self::HARI_INI.' 08:00');

        $this->postJson("/admin/booking/{$booking->kode_booking}/keluar")->assertStatus(409);
    }

    public function test_struk_tandai_keluar_tampil_di_halaman_bukti_booking(): void
    {
        $booking = $this->dibatalkanSistem();
        $this->pukul('12:40');
        $this->post("/admin/booking/{$booking->kode_booking}/keluar")->assertRedirect(route('booking.show', $booking));

        $this->get("/booking/{$booking->kode_booking}")
            ->assertOk()
            ->assertSee('Struk keluar')
            ->assertSee('Rp20.000');
    }

    // ---------- Halaman petugas ----------

    public function test_formulir_check_in_menampilkan_booking_hari_ini_yang_belum_check_in(): void
    {
        $hariIni = $this->booking('A1-01', self::HARI_INI.' 09:00');
        $besok = $this->booking('A1-02', '2026-11-24 09:00');

        $this->get('/parkir/mobil')
            ->assertOk()
            ->assertSee($hariIni->kode_booking)
            ->assertDontSee($besok->kode_booking);
    }
}
