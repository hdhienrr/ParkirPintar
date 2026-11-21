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

    'booking' => [
        // Jam operasional: waktu mulai dan selesai booking harus di dalam rentang ini.
        'jam_buka' => 6,
        'jam_tutup' => 22,

        // Durasi booking dalam jam penuh.
        'durasi_min_jam' => 1,
        'durasi_maks_jam' => 8,

        // Waktu mulai paling jauh sekian hari dari sekarang.
        'hari_ke_depan_maks' => 7,

        // Jeda minimal antara dua booking pada slot yang sama.
        'jeda_menit' => 60,

        // Satu plat paling banyak ada di sekian booking aktif sekaligus.
        'plat_maks_aktif' => 2,

        // Check-in paling cepat sekian menit sebelum mulai, paling lambat sekian menit sesudahnya.
        'toleransi_awal_menit' => 15,
        'toleransi_terlambat_menit' => 30,

        // Overstay: lama booking berikutnya menunggu slot yang masih ditempati sebelum dibatalkan sistem,
        // dan batas waktu kendaraan dari booking yang dibatalkan itu untuk keluar tanpa denda.
        'tunggu_penghalang_menit' => 15,
        'keluar_tanpa_denda_menit' => 15,
    ],

    'peraturan' => [
        // Jam booking contoh untuk garis waktu kedatangan di halaman peraturan.
        'jam_contoh' => 8,
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
