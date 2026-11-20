<?php

namespace Tests\Feature;

use App\Enums\Peran;
use App\Models\User;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminSeederTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_admin_dibuat_dari_konfigurasi_env(): void
    {
        config(['parkir.admin.email' => 'admin@contoh.test', 'parkir.admin.password' => 'kata-sandi-uji']);

        $this->seed(AdminSeeder::class);

        $admin = User::where('email', 'admin@contoh.test')->firstOrFail();
        $this->assertSame(Peran::Admin, $admin->role);
        $this->assertTrue(Hash::check('kata-sandi-uji', $admin->password));
    }

    public function test_seeder_admin_aman_dijalankan_dua_kali(): void
    {
        config(['parkir.admin.email' => 'admin@contoh.test', 'parkir.admin.password' => 'kata-sandi-uji']);

        $this->seed(AdminSeeder::class);
        $this->seed(AdminSeeder::class);

        $this->assertSame(1, User::where('email', 'admin@contoh.test')->count());
    }

    public function test_admin_tidak_dibuat_jika_env_kosong(): void
    {
        config(['parkir.admin.email' => null, 'parkir.admin.password' => null]);

        $this->seed(AdminSeeder::class);

        $this->assertSame(0, User::count());
    }
}
