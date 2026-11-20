<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AksesTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected $seeder = DatabaseSeeder::class;

    public static function halamanAdmin(): array
    {
        return [
            'parkir mobil' => ['/parkir/mobil'],
            'parkir motor' => ['/parkir/motor'],
            'daftar area' => ['/areas'],
            'denah slot' => ['/areas/1/slots'],
            'laporan okupansi' => ['/reports/occupancy'],
            'laporan pendapatan' => ['/reports/revenue'],
            'semua booking' => ['/admin/booking'],
            'kendaraan sedang parkir' => ['/admin/kendaraan'],
        ];
    }

    #[DataProvider('halamanAdmin')]
    public function test_tamu_diarahkan_ke_halaman_masuk(string $halaman): void
    {
        $this->get($halaman)->assertRedirect(route('login'));
    }

    #[DataProvider('halamanAdmin')]
    public function test_user_biasa_tidak_bisa_membuka_halaman_admin(string $halaman): void
    {
        $this->actingAs(User::factory()->create())->get($halaman)->assertForbidden();
    }

    #[DataProvider('halamanAdmin')]
    public function test_admin_bisa_membuka_halaman_admin(string $halaman): void
    {
        $this->actingAs(User::factory()->admin()->create())->getJson($halaman)->assertOk();
    }

    public function test_user_biasa_tidak_bisa_check_in(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/checkin', ['kode_booking' => 'BK-APAPUN'])
            ->assertForbidden();
    }

    public function test_beranda_dan_health_tetap_publik(): void
    {
        $this->get('/')->assertOk();
        $this->getJson('/health')->assertOk();
    }
}
