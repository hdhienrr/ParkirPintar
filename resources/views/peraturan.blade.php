@extends('layouts.app')

@use('App\Support\Format')

@section('label', 'Informasi')
@section('judul', 'Peraturan parkir')

@section('isi')
    <p class="peraturan-pengantar">Hal yang perlu Anda tahu sebelum booking. Angka di halaman ini selalu sama dengan aturan yang dipakai sistem.</p>

    <div class="fakta">
        <div>
            <span>Jam buka</span>
            <strong>{{ Format::jamBulat($booking['jam_buka']) }}–{{ Format::jamBulat($booking['jam_tutup']) }}</strong>
            <small>Setiap hari</small>
        </div>
        <div>
            <span>Lama booking</span>
            <strong>{{ $booking['durasi_min_jam'] }}–{{ $booking['durasi_maks_jam'] }} jam</strong>
            <small>Mulai tepat di awal jam</small>
        </div>
        <div>
            <span>Pesan paling jauh</span>
            <strong>{{ $booking['hari_ke_depan_maks'] }} hari</strong>
            <small>Dari hari ini</small>
        </div>
        <div class="fakta-sorot">
            <span>Biaya booking</span>
            <strong>Gratis</strong>
            <small>Bayar parkir saat keluar</small>
        </div>
    </div>

    <section class="peraturan-bagian" aria-labelledby="judul-datang">
        <h2 id="judul-datang">Datang tepat waktu</h2>
        <p>Contoh untuk booking pukul {{ Format::jam($contoh['mulai']) }}.</p>
        <ol class="garis-waktu">
            <li class="titik-aman">
                <b>{{ Format::jam($contoh['paling_cepat']) }}</b>
                <span>Paling cepat check-in ({{ $booking['toleransi_awal_menit'] }} menit sebelum)</span>
            </li>
            <li class="titik-mulai">
                <b>{{ Format::jam($contoh['mulai']) }}</b>
                <span>Jam booking dimulai</span>
            </li>
            <li class="titik-aman">
                <b>{{ Format::jam($contoh['batas_terlambat']) }}</b>
                <span>Batas terlambat ({{ $booking['toleransi_terlambat_menit'] }} menit sesudah)</span>
            </li>
            <li class="titik-hangus">
                <b>{{ Format::jam($contoh['hangus']) }}</b>
                <span>Booking hangus, slot dilepas</span>
            </li>
        </ol>
    </section>

    <section class="peraturan-bagian" aria-labelledby="judul-tarif">
        <h2 id="judul-tarif">Tarif parkir</h2>
        <p>Dihitung dari waktu masuk sampai keluar yang sebenarnya, bukan dari lama booking. Dibayar di tempat saat keluar.</p>
        <div class="tarif-grid">
            @foreach ($tarif as $t)
                @include('partials.kartu-tarif', ['tarif' => $t])
            @endforeach
        </div>
    </section>

    <section class="peraturan-bagian" aria-labelledby="judul-lewat">
        <h2 id="judul-lewat">Kalau lewat jam selesai</h2>
        <p>Anda tidak diusir dan tarif tetap berjalan. Denda hanya ada bila keterlambatan mengganggu booking orang lain.</p>
        <div class="kartu-denda-grid">
            <article class="kartu-denda kartu-denda-pelaku">
                <h3>Anda terlambat keluar</h3>
                <p>Jika booking berikutnya tidak mendapat slot setelah menunggu {{ $booking['tunggu_penghalang_menit'] }} menit, Anda dikenai denda per jam.</p>
                <div class="angka-denda">
                    @foreach ($tarif as $t)
                        <div>
                            <span>{{ $t->jenis }}</span>
                            <strong>{{ Format::rupiah($t->denda_pelaku_per_jam) }}/jam</strong>
                            <small>maks {{ Format::rupiah($t->denda_pelaku_maks_harian) }}/hari</small>
                        </div>
                    @endforeach
                </div>
            </article>
            <article class="kartu-denda">
                <h3>Booking Anda dibatalkan sistem</h3>
                <p>Tanpa biaya pembatalan. Keluar dalam {{ $booking['keluar_tanpa_denda_menit'] }} menit gratis, lewat dari itu ada denda ringan per jam.</p>
                <div class="angka-denda">
                    @foreach ($tarif as $t)
                        <div>
                            <span>{{ $t->jenis }}</span>
                            <strong>{{ Format::rupiah($t->denda_korban_per_jam) }}/jam</strong>
                            <small>maks {{ Format::rupiah($t->denda_korban_maks_harian) }}/hari</small>
                        </div>
                    @endforeach
                </div>
            </article>
        </div>
    </section>

    <p class="catatan-akun">
        Satu akun hanya punya <b>satu booking aktif</b>, dan satu plat nomor paling banyak di <b>{{ $booking['plat_maks_aktif'] }} booking aktif</b>.
        Booking bisa dibatalkan sendiri sebelum jam mulai.
    </p>
@endsection
