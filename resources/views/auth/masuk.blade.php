@extends('layouts.app')

@section('label', 'Akun')
@section('judul', 'Masuk')

@section('isi')
    <div class="panel kartu-akun">
        <div class="panel-isi">
            @include('partials.pesan')

            <form method="POST" action="{{ route('login') }}" class="kolom-form">
                @csrf
                <div class="isian">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus>
                </div>
                <div class="isian">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required>
                </div>
                <button class="tombol tombol-utama tombol-penuh">Masuk</button>
            </form>

            <p class="kartu-akun-catatan">Belum punya akun? <a href="{{ route('register') }}">Daftar di sini</a>.</p>
        </div>
    </div>
@endsection
