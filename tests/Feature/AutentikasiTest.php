<?php

namespace Tests\Feature;

use App\Enums\Peran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutentikasiTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private const PASSWORD = 'rahasia-uji-123';

    private function dataDaftar(array $ubah = []): array
    {
        return [
            'name' => 'Budi',
            'email' => 'budi@contoh.test',
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
            ...$ubah,
        ];
    }

    public function test_halaman_masuk_dan_daftar_bisa_dibuka_tanpa_login(): void
    {
        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();
    }

    public function test_daftar_membuat_akun_user_dan_langsung_masuk(): void
    {
        $this->post('/register', $this->dataDaftar())->assertRedirect(route('beranda'));

        $user = User::where('email', 'budi@contoh.test')->firstOrFail();
        $this->assertSame(Peran::User, $user->role);
        $this->assertAuthenticatedAs($user);
    }

    public function test_daftar_dengan_role_admin_tetap_menjadi_user(): void
    {
        $this->post('/register', $this->dataDaftar(['role' => 'admin']));

        $this->assertSame(Peran::User, User::where('email', 'budi@contoh.test')->value('role'));
    }

    public function test_password_kurang_dari_8_karakter_ditolak(): void
    {
        $this->post('/register', $this->dataDaftar(['password' => 'pendek1', 'password_confirmation' => 'pendek1']))
            ->assertSessionHasErrors('password');

        $this->assertGuest();
    }

    public function test_password_disimpan_dalam_bentuk_hash(): void
    {
        $this->post('/register', $this->dataDaftar());

        $this->assertNotSame(self::PASSWORD, User::where('email', 'budi@contoh.test')->value('password'));
    }

    public function test_user_bisa_masuk_dengan_password_benar(): void
    {
        $user = User::factory()->create(['password' => self::PASSWORD]);

        $this->post('/login', ['email' => $user->email, 'password' => self::PASSWORD])->assertRedirect(route('beranda'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_admin_diarahkan_ke_halaman_parkir_setelah_masuk(): void
    {
        $admin = User::factory()->admin()->create(['password' => self::PASSWORD]);

        $this->post('/login', ['email' => $admin->email, 'password' => self::PASSWORD])
            ->assertRedirect(route('parkir', 'mobil'));
    }

    public function test_masuk_dengan_password_salah_ditolak(): void
    {
        $user = User::factory()->create(['password' => self::PASSWORD]);

        $this->post('/login', ['email' => $user->email, 'password' => 'salah-sekali'])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_masuk_dikunci_setelah_terlalu_banyak_percobaan_gagal(): void
    {
        $user = User::factory()->create(['password' => self::PASSWORD]);

        for ($i = 0; $i < config('parkir.akun.login_maks_percobaan'); $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'salah-sekali']);
        }

        // Password benar pun ditolak selama masih dikunci.
        $this->post('/login', ['email' => $user->email, 'password' => self::PASSWORD])->assertSessionHasErrors('email');

        $this->assertStringStartsWith('Terlalu banyak percobaan masuk', session('errors')->first('email'));
        $this->assertGuest();
    }

    public function test_session_diganti_setelah_masuk(): void
    {
        $user = User::factory()->create(['password' => self::PASSWORD]);
        $this->get('/login');
        $sessionLama = session()->getId();

        $this->post('/login', ['email' => $user->email, 'password' => self::PASSWORD]);

        $this->assertNotSame($sessionLama, session()->getId());
    }

    public function test_keluar_menghapus_session(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post('/logout')->assertRedirect(route('beranda'));

        $this->assertGuest();
    }
}
