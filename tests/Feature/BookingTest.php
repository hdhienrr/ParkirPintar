<?php

namespace Tests\Feature;

use App\Enums\StatusBooking;
use App\Exceptions\BookingDitolak;
use App\Models\Booking;
use App\Models\SlotParkir;
use App\Models\User;
use App\Services\LayananBooking;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Aturan booking. Waktu sekarang dibekukan: Senin, 23 November 2026 pukul 07.00.
 */
class BookingTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected $seeder = DatabaseSeeder::class;

    private const HARI_INI = '2026-11-23';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse(self::HARI_INI.' 07:00'));
        $this->user = User::factory()->create();
    }

    private function slot(string $kode): SlotParkir
    {
        return SlotParkir::where('kode_slot', $kode)->firstOrFail();
    }

    /** Booking hari ini 10.00 sampai 12.00 di A1-01, kecuali diubah. */
    private function booking(array $ubah = [], ?User $user = null): TestResponse
    {
        $data = [
            'plat' => 'B 1234 XY',
            'jenis' => 'mobil',
            'tanggal' => self::HARI_INI,
            'jam' => 10,
            'durasi' => 2,
            'slot' => 'A1-01',
            ...$ubah,
        ];
        $data['slot_id'] = $this->slot($data['slot'])->id;
        unset($data['slot']);

        return $this->actingAs($user ?? $this->user)->postJson('/booking', $data);
    }

    /** Booking milik akun lain yang sudah ada di slot tertentu. */
    private function bookingLain(string $kodeSlot, string $mulai, int $jam, array $ubah = []): Booking
    {
        return Booking::factory()
            ->for($this->slot($kodeSlot), 'slot')
            ->jendela($mulai, $jam)
            ->create($ubah);
    }

    public function test_user_bisa_booking_slot_yang_bebas(): void
    {
        $kode = $this->booking()->assertCreated()->json('data.kode_booking');

        $booking = Booking::where('kode_booking', $kode)->firstOrFail();
        $this->assertSame(StatusBooking::Dipesan, $booking->status);
        $this->assertSame('B1234XY', $booking->plat);
        $this->assertSame(self::HARI_INI.' 12:00:00', $booking->waktu_selesai->toDateTimeString());
    }

    public function test_booking_bentrok_pada_slot_yang_sama_ditolak(): void
    {
        $this->bookingLain('A1-01', self::HARI_INI.' 10:00', 2);

        $this->booking(['jam' => 11])->assertStatus(409);
    }

    public function test_jeda_kurang_dari_satu_jam_dengan_booking_lain_ditolak(): void
    {
        $this->bookingLain('A1-01', self::HARI_INI.' 10:00', 2);

        $this->booking(['jam' => 12])->assertStatus(409);
    }

    public function test_jeda_tepat_satu_jam_dengan_booking_lain_diterima(): void
    {
        $this->bookingLain('A1-01', self::HARI_INI.' 10:00', 2);

        $this->booking(['jam' => 13])->assertCreated();
    }

    public function test_slot_langsung_bisa_dibooking_setelah_booking_sebelumnya_selesai(): void
    {
        // Booking 06.00 sampai 09.00 sudah check-out lebih awal pukul 06.50.
        $this->bookingLain('A1-01', self::HARI_INI.' 06:00', 3, ['waktu_keluar' => self::HARI_INI.' 06:50'])
            ->forceFill(['status' => StatusBooking::Selesai])->save();

        $this->booking(['jam' => 8])->assertCreated();
    }

    public function test_booking_sebelum_jam_buka_ditolak(): void
    {
        $this->booking(['tanggal' => '2026-11-24', 'jam' => 5])->assertStatus(422);
    }

    public function test_booking_yang_selesai_setelah_jam_tutup_ditolak(): void
    {
        $this->booking(['jam' => 21, 'durasi' => 2])->assertStatus(422);
    }

    public function test_durasi_di_bawah_satu_jam_ditolak(): void
    {
        $this->booking(['durasi' => 0])->assertStatus(422);
    }

    public function test_durasi_di_atas_delapan_jam_ditolak(): void
    {
        $this->booking(['jam' => 8, 'durasi' => 9])->assertStatus(422);
    }

    public function test_booking_di_masa_lalu_ditolak(): void
    {
        $this->booking(['jam' => 6])->assertStatus(422);
    }

    public function test_booking_lebih_dari_tujuh_hari_ke_depan_ditolak(): void
    {
        $this->booking(['tanggal' => '2026-12-01'])->assertStatus(422);
    }

    public function test_booking_tepat_tujuh_hari_ke_depan_diterima(): void
    {
        $this->booking(['tanggal' => '2026-11-30', 'jam' => 21, 'durasi' => 1])->assertCreated();
    }

    public function test_jam_mulai_harus_jam_bulat(): void
    {
        $this->booking(['jam' => '8.30'])->assertStatus(422)->assertJsonValidationErrors('jam');
    }

    public function test_layanan_menolak_waktu_mulai_yang_bukan_jam_bulat(): void
    {
        $this->expectException(BookingDitolak::class);

        app(LayananBooking::class)->buat(
            $this->user, 'B 1 A', 'mobil', $this->slot('A1-01')->id,
            CarbonImmutable::parse(self::HARI_INI.' 10:30'), 2,
        );
    }

    public function test_jenis_kendaraan_harus_sesuai_slot(): void
    {
        $this->booking(['jenis' => 'motor'])->assertStatus(422);
    }

    public function test_akun_dengan_booking_aktif_tidak_bisa_booking_lagi(): void
    {
        $this->booking()->assertCreated();

        $this->booking(['plat' => 'B 9 Z', 'slot' => 'A1-02'])->assertStatus(409);
    }

    public static function statusTidakBerlaku(): array
    {
        return [
            'selesai' => [StatusBooking::Selesai],
            'dibatalkan' => [StatusBooking::Dibatalkan],
            'hangus' => [StatusBooking::Hangus],
            'dibatalkan sistem' => [StatusBooking::DibatalkanSistem],
        ];
    }

    #[DataProvider('statusTidakBerlaku')]
    public function test_akun_bisa_booking_lagi_setelah_booking_sebelumnya_tidak_berlaku(StatusBooking $status): void
    {
        $kode = $this->booking()->json('data.kode_booking');
        Booking::where('kode_booking', $kode)->update(['status' => $status]);

        $this->booking(['plat' => 'B 9 Z', 'slot' => 'A1-02'])->assertCreated();
    }

    public function test_booking_ditolak_jika_semua_slot_jenis_itu_sudah_terbooking(): void
    {
        // Sisakan satu slot motor saja, lalu booking slot itu oleh akun lain.
        SlotParkir::where('jenis', 'motor')->where('kode_slot', '!=', 'M1-01')->delete();
        $this->bookingLain('M1-01', self::HARI_INI.' 10:00', 2);

        $this->booking(['jenis' => 'motor', 'slot' => 'M1-01'])
            ->assertStatus(409)
            ->assertJsonPath('message', 'Semua slot motor sudah dibooking pada jam itu. Pilih jam lain.');
    }

    public function test_plat_boleh_ada_di_dua_booking_aktif(): void
    {
        $this->bookingLain('A1-02', self::HARI_INI.' 10:00', 2, ['plat' => 'B 1234 XY']);

        $this->booking()->assertCreated();
    }

    public function test_booking_ketiga_dengan_plat_yang_sama_ditolak(): void
    {
        $this->bookingLain('A1-02', self::HARI_INI.' 10:00', 2, ['plat' => 'B 1234 XY']);
        $this->bookingLain('A1-03', self::HARI_INI.' 14:00', 2, ['plat' => 'B1234XY']);

        $this->booking()->assertStatus(409)->assertJsonPath('message', 'Plat B1234XY sudah ada di 2 booking aktif. Tunggu salah satunya selesai.');
    }

    public function test_plat_bisa_dipakai_lagi_setelah_salah_satu_booking_tidak_berlaku(): void
    {
        $this->bookingLain('A1-02', self::HARI_INI.' 10:00', 2, ['plat' => 'B 1234 XY']);
        $this->bookingLain('A1-03', self::HARI_INI.' 14:00', 2, ['plat' => 'B 1234 XY'])
            ->forceFill(['status' => StatusBooking::Dibatalkan])->save();

        $this->booking()->assertCreated();
    }

    public function test_booking_hangus_jika_tidak_dicoba_check_in_sampai_30_menit_setelah_mulai(): void
    {
        $booking = $this->bookingLain('A1-01', self::HARI_INI.' 08:00', 2);

        $this->travelTo(CarbonImmutable::parse(self::HARI_INI.' 08:31'));
        app(LayananBooking::class)->segarkanStatus();

        $this->assertSame(StatusBooking::Hangus, $booking->refresh()->status);
        $this->assertSame(0, $booking->denda);
    }

    public function test_booking_belum_hangus_tepat_30_menit_setelah_mulai(): void
    {
        $booking = $this->bookingLain('A1-01', self::HARI_INI.' 08:00', 2);

        $this->travelTo(CarbonImmutable::parse(self::HARI_INI.' 08:30'));
        app(LayananBooking::class)->segarkanStatus();

        $this->assertSame(StatusBooking::Dipesan, $booking->refresh()->status);
    }

    public function test_booking_yang_sedang_menunggu_tidak_menjadi_hangus(): void
    {
        $booking = $this->bookingLain('A1-01', self::HARI_INI.' 08:00', 2);
        $booking->forceFill(['status' => StatusBooking::Menunggu, 'menunggu_sejak' => self::HARI_INI.' 08:00'])->save();

        $this->travelTo(CarbonImmutable::parse(self::HARI_INI.' 09:00'));
        app(LayananBooking::class)->segarkanStatus();

        $this->assertSame(StatusBooking::Menunggu, $booking->refresh()->status);
    }

    public function test_booking_yang_hangus_membebaskan_akun_untuk_booking_lagi(): void
    {
        $this->bookingLain('A1-01', self::HARI_INI.' 08:00', 2, ['user_id' => $this->user->id]);

        $this->travelTo(CarbonImmutable::parse(self::HARI_INI.' 08:31'));

        $this->booking(['jam' => 10, 'slot' => 'A1-05'])->assertCreated();
    }

    public function test_user_bisa_membatalkan_booking_sebelum_waktu_mulai(): void
    {
        $kode = $this->booking()->json('data.kode_booking');

        $this->post("/booking/{$kode}/batal")->assertRedirect(route('booking.index'));

        $this->assertSame(StatusBooking::Dibatalkan, Booking::where('kode_booking', $kode)->value('status'));
    }

    public function test_booking_tidak_bisa_dibatalkan_setelah_waktu_mulai(): void
    {
        $kode = $this->booking()->json('data.kode_booking');

        $this->travelTo(CarbonImmutable::parse(self::HARI_INI.' 10:05'));

        $this->postJson("/booking/{$kode}/batal")->assertStatus(409);
    }

    public function test_user_lain_tidak_bisa_membatalkan_booking(): void
    {
        $booking = $this->bookingLain('A1-01', self::HARI_INI.' 10:00', 2);

        $this->actingAs($this->user)->post("/booking/{$booking->kode_booking}/batal")->assertForbidden();
    }

    public function test_ketersediaan_menandai_slot_yang_sudah_dibooking(): void
    {
        $this->bookingLain('A1-01', self::HARI_INI.' 10:00', 2);

        $respons = $this->actingAs($this->user)
            ->getJson('/booking/ketersediaan?'.http_build_query(['jenis' => 'mobil', 'tanggal' => self::HARI_INI, 'jam' => 10, 'durasi' => 2]))
            ->assertOk();

        $slot = collect($respons->json('data'))->firstWhere('nama', 'Mobil Lantai 1')['slot'];
        $this->assertSame('terisi', collect($slot)->firstWhere('kode_slot', 'A1-01')['status']);
        $this->assertSame('kosong', collect($slot)->firstWhere('kode_slot', 'A1-02')['status']);
    }
}
