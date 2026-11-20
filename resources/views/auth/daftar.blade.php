@extends('layouts.app')

@section('label', 'Akun')
@section('judul', 'Daftar akun')

@section('isi')
    <div class="panel kartu-akun">
        <div class="panel-isi">
            @include('partials.pesan')

            <form method="POST" action="{{ route('register') }}" class="kolom-form">
                @csrf
                <div class="isian">
                    <label for="name">Nama</label>
                    <input id="name" name="name" value="{{ old('name') }}" maxlength="100" autocomplete="name" required autofocus>
                </div>
                <div class="isian">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required>
                </div>
                <div class="isian">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" minlength="{{ config('parkir.akun.password_min') }}" autocomplete="new-password" required>
                    <span class="petunjuk">Minimal {{ config('parkir.akun.password_min') }} karakter.</span>
                </div>
                <div class="isian">
                    <label for="password_confirmation">Ulangi password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
                </div>
                <button class="tombol tombol-utama tombol-penuh">Daftar</button>
            </form>

            <p class="kartu-akun-catatan">Sudah punya akun? <a href="{{ route('login') }}">Masuk</a>.</p>
        </div>
    </div>
@endsection
