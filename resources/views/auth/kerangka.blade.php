{{--
    Kerangka halaman masuk dan daftar: foto gedung di kiri, formulir di kanan.
    Foto memakai view-transition-name yang sama dengan foto hero di beranda, jadi saat berpindah
    halaman foto bergeser dari kanan ke kiri, dan formulir masuk dari kanan (lihat CSS "Transisi halaman").

    Section yang diisi halaman anak: foto-judul, foto-teks, foto-chip, akun-judul, akun-sub, formulir, catatan.
--}}
@extends('layouts.app')

@section('kelas-body', 'halaman-akun')

@section('penuh')
    <section class="akun">
        <div class="akun-foto" role="img" aria-label="Lantai parkir gedung dengan pilar beton">
            <h2>@yield('foto-judul')</h2>
            <p>@yield('foto-teks')</p>
            <div class="akun-chip">@yield('foto-chip')</div>
        </div>

        <div class="akun-form">
            <div class="akun-kotak">
                <h1 class="akun-judul">@yield('akun-judul')</h1>
                <p class="akun-sub">@yield('akun-sub')</p>

                @include('partials.pesan')

                @yield('formulir')

                <p class="akun-catatan">@yield('catatan')</p>
            </div>
        </div>
    </section>
@endsection

@push('skrip')
    <script>
        // Ikon mata menampilkan atau menyembunyikan password yang sedang diketik.
        document.querySelectorAll('[data-lihat-sandi]').forEach((tombol) => {
            const isian = document.getElementById(tombol.dataset.lihatSandi);
            tombol.addEventListener('click', () => {
                const tampil = isian.type === 'password';
                isian.type = tampil ? 'text' : 'password';
                const label = tampil ? 'Sembunyikan password' : 'Tampilkan password';
                tombol.setAttribute('aria-pressed', String(tampil));
                tombol.setAttribute('aria-label', label);
                tombol.title = label;
            });
        });
    </script>
@endpush
