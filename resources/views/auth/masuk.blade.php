@extends('auth.kerangka')

@section('judul', 'Masuk')

@section('foto-judul', 'Parkir lebih cepat, tanpa cari-cari slot')
@section('foto-teks', 'Masuk untuk booking slot dari denah tiap lantai, lihat bukti booking, dan karcis parkir Anda.')
@section('foto-chip')
    <span>1 · Booking slot</span><span>2 · Datang sesuai jadwal</span><span>3 · Bayar saat keluar</span>
@endsection

@section('akun-judul', 'Selamat datang')
@section('akun-sub', 'Masuk dengan email dan password akun Anda.')

@section('formulir')
    <form method="POST" action="{{ route('login') }}" class="kolom-form">
        @csrf
        <div class="isian">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" placeholder="nama@email.com" required autofocus>
        </div>
        <div class="isian">
            <label for="password">Password</label>
            <div class="isian-sandi">
                <input id="password" name="password" type="password" autocomplete="current-password" required>
                @include('partials.tombol-lihat-sandi', ['untuk' => 'password'])
            </div>
        </div>
        <button class="tombol tombol-utama tombol-penuh">Masuk</button>
    </form>
@endsection

@section('catatan')
    Belum punya akun? <a href="{{ route('register') }}">Daftar gratis</a>
@endsection
