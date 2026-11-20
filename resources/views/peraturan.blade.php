@extends('layouts.app')

@use('App\Support\Format')

@section('label', 'Informasi')
@section('judul', 'Peraturan parkir')

@section('isi')
    <div class="peraturan">
        <section class="panel">
            <div class="panel-isi">
                <h2>Booking</h2>
                <ul>
                    <li>Parkir buka pukul {{ Format::jamBulat($booking['jam_buka']) }} sampai {{ Format::jamBulat($booking['jam_tutup']) }}.</li>
                    <li>Booking gratis, {{ $booking['durasi_min_jam'] }} sampai {{ $booking['durasi_maks_jam'] }} jam, dimulai tepat di awal jam, dan paling jauh {{ $booking['hari_ke_depan_maks'] }} hari ke depan.</li>
                    <li>Satu akun hanya boleh punya satu booking aktif. Satu plat nomor paling banyak ada di {{ $booking['plat_maks_aktif'] }} booking aktif.</li>
                    <li>Datang paling cepat {{ $booking['toleransi_awal_menit'] }} menit sebelum jam booking dan paling lambat {{ $booking['toleransi_terlambat_menit'] }} menit sesudahnya. Lewat dari itu, booking hangus.</li>
                    <li>Booking bisa dibatalkan sendiri sebelum jam mulai.</li>
                </ul>
            </div>
        </section>

        <section class="panel">
            <div class="panel-isi">
                <h2>Tarif</h2>
                <ul>
                    @foreach ($tarif as $t)
                        <li>
                            {{ ucfirst($t->jenis) }}: {{ Format::rupiah($t->tarif_jam_pertama) }} jam pertama, lalu {{ Format::rupiah($t->tarif_jam_berikutnya) }} per jam,
                            paling banyak {{ Format::rupiah($t->maks_harian) }} per hari.
                        </li>
                    @endforeach
                    <li>{{ $menitGratis }} menit pertama gratis, dan sisa jam dibulatkan ke atas.</li>
                    <li>Biaya dihitung dari waktu masuk sampai keluar yang sebenarnya, bukan dari lama booking.</li>
                    <li>Pembayaran dilakukan di tempat saat keluar.</li>
                </ul>
            </div>
        </section>

        <section class="panel">
            <div class="panel-isi">
                <h2>Melewati jam selesai</h2>
                <ul>
                    <li>Kendaraan yang melewati jam selesai tidak diusir, dan tarif tetap berjalan.</li>
                    <li>
                        Jika keterlambatan membuat booking orang lain tidak mendapat slot setelah menunggu {{ $booking['tunggu_penghalang_menit'] }} menit,
                        kendaraan yang terlambat dikenai denda per jam:
                        @foreach ($tarif as $t)
                            {{ $t->jenis }} {{ Format::rupiah($t->denda_pelaku_per_jam) }} (paling banyak {{ Format::rupiah($t->denda_pelaku_maks_harian) }} per hari){{ $loop->last ? '.' : ',' }}
                        @endforeach
                    </li>
                    <li>
                        Jika booking Anda dibatalkan sistem karena slot masih ditempati, tidak ada biaya pembatalan.
                        Keluar dalam {{ $booking['keluar_tanpa_denda_menit'] }} menit tanpa biaya. Lewat dari itu, ada denda ringan per jam:
                        @foreach ($tarif as $t)
                            {{ $t->jenis }} {{ Format::rupiah($t->denda_korban_per_jam) }} (paling banyak {{ Format::rupiah($t->denda_korban_maks_harian) }} per hari){{ $loop->last ? '.' : ',' }}
                        @endforeach
                    </li>
                </ul>
            </div>
        </section>
    </div>
@endsection
