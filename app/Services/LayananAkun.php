<?php

namespace App\Services;

use App\Enums\Peran;
use App\Models\User;

class LayananAkun
{
    /**
     * Pendaftaran publik selalu menghasilkan peran user, apa pun isi formulirnya.
     */
    public function daftar(string $nama, string $email, string $password): User
    {
        return User::create([
            'name' => $nama,
            'email' => $email,
            'password' => $password,
        ]);
    }

    /**
     * Membuat atau memperbarui akun admin. Aman dijalankan berulang.
     */
    public function pastikanAdmin(string $nama, string $email, string $password): User
    {
        $admin = User::firstOrNew(['email' => $email]);
        $admin->fill(['name' => $nama, 'password' => $password]);
        $admin->role = Peran::Admin;
        $admin->save();

        return $admin;
    }
}
