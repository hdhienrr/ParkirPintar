@extends('auth.kerangka')

@use('App\Support\Format')

@php($aturan = config('parkir.booking'))
@php($passwordMin = config('parkir.akun.password_min'))

@section('judul', 'Daftar akun')

@section('foto-judul', 'Booking parkir gratis, bayar saat keluar')
@section('foto-teks', "Buat akun sekali, lalu booking slot kapan saja sampai {$aturan['hari_ke_depan_maks']} hari ke depan.")
@section('foto-chip')
    <span>Booking gratis</span>
    <span>{{ Format::jamBulat($aturan['jam_buka']) }}–{{ Format::jamBulat($aturan['jam_tutup']) }}</span>
    <span>{{ $aturan['durasi_min_jam'] }}–{{ $aturan['durasi_maks_jam'] }} jam</span>
@endsection

@section('akun-judul', 'Buat akun')
@section('akun-sub', 'Hanya butuh nama, email, dan password.')

@section('formulir')
    <form method="POST" action="{{ route('register') }}" class="kolom-form">
        @csrf
        <div class="isian">
            <label for="name">Nama</label>
            <input id="name" name="name" value="{{ old('name') }}" maxlength="100" autocomplete="name" placeholder="Nama lengkap" required autofocus>
        </div>
        <div class="isian">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" placeholder="nama@email.com" required>
        </div>
        <div class="isian-ganda">
            <div class="isian">
                <label for="password">Password</label>
                <div class="isian-sandi">
                    <input id="password" name="password" type="password" minlength="{{ $passwordMin }}" autocomplete="new-password" placeholder="Min. {{ $passwordMin }} karakter" required>
                    @include('partials.tombol-lihat-sandi', ['untuk' => 'password'])
                </div>
            </div>
            <div class="isian">
                <label for="password_confirmation">Ulangi password</label>
                <div class="isian-sandi">
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
                    @include('partials.tombol-lihat-sandi', ['untuk' => 'password_confirmation'])
                </div>
            </div>
        </div>
        <button class="tombol tombol-utama tombol-penuh">Daftar</button>
    </form>
@endsection

@section('catatan')
    Sudah punya akun? <a href="{{ route('login') }}">Masuk</a>
@endsection
