<?php

/*
 * Angka aturan ParkirPintar yang bukan tarif. Tarif dan denda ada di tabel tarif.
 */
return [

    'area' => [
        // Nomor lantai tertinggi yang boleh dibuat.
        'lantai_maks' => 20,

        // Jumlah slot per lantai saat area baru dibuat lewat formulir.
        'slot_bawaan' => 72,

        // Batas atas slot per lantai (12 zona).
        'slot_maks' => 216,
    ],

    'laporan' => [
        // Rentang bawaan laporan pendapatan bila filter tanggal kosong.
        'hari_pendapatan' => 30,
    ],

    'denah' => [
        // Jeda penyegaran denah di halaman parkir.
        'segar_detik' => 20,
    ],

    'beranda' => [
        // Lama parkir untuk contoh biaya di bagian tarif (2 jam 30 menit).
        'menit_contoh_biaya' => 150,
    ],

    // Akun admin pertama, dibuat oleh AdminSeeder. Nilainya hanya dari .env.
    'admin' => [
        'nama' => env('ADMIN_NAME', 'Admin ParkirPintar'),
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],

    'akun' => [
        'password_min' => 8,

        // Batas tebak password: percobaan gagal per email dan IP sebelum dikunci sementara.
        'login_maks_percobaan' => 5,
        'login_kunci_detik' => 60,
    ],

];
