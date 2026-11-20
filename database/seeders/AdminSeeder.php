<?php

namespace Database\Seeders;

use App\Services\LayananAkun;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(LayananAkun $layanan): void
    {
        $email = config('parkir.admin.email');
        $password = config('parkir.admin.password');

        if (blank($email) || blank($password)) {
            $this->command?->warn('ADMIN_EMAIL atau ADMIN_PASSWORD belum diisi di .env, akun admin tidak dibuat.');

            return;
        }

        $layanan->pastikanAdmin(config('parkir.admin.nama'), $email, $password);
    }
}
