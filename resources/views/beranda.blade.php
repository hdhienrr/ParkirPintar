@extends('layouts.app')

@use('App\Support\Format')

@section('judul', 'Beranda')
@section('kelas-body', 'beranda')

@section('penuh')
    <section class="hero">
        <div class="hero-teks">
            <p class="label">Sistem parkir gedung</p>
            <h1>Parkir lebih cepat, <span class="hero-sorot">tanpa cari-cari slot</span></h1>
            <p class="hero-deskripsi">Booking slot langsung dari denah tiap lantai, datang sesuai jadwal, dan bayar sesuai lama parkir saat keluar.</p>
            <div class="hero-aksi">
                @can('admin')
                    <a href="{{ route('parkir', 'mobil') }}" class="tombol tombol-utama">Check-in mobil</a>
                    <a href="{{ route('parkir', 'motor') }}" class="tombol">Check-in motor</a>
                @elsecan('booking')
                    <a href="{{ route('booking.create') }}" class="tombol tombol-utama">Booking sekarang</a>
                    <a href="{{ route('booking.index') }}" class="tombol">Booking saya</a>
                @else
                    <a href="{{ route('booking.create') }}" class="tombol tombol-utama">Booking sekarang</a>
                    <a href="{{ route('peraturan') }}" class="tombol">Lihat peraturan</a>
                @endcan
            </div>
        </div>
        <div class="hero-foto" role="img" aria-label="Lantai parkir gedung dengan pilar beton"></div>
    </section>

    <section class="bagian" aria-labelledby="judul-area">
        <div class="bagian-kepala" data-muncul>
            <div>
                <p class="label">Ketersediaan sekarang</p>
                <h2 id="judul-area">Slot kosong per lantai</h2>
            </div>
        </div>

        <div class="kartu-grid">
            @forelse ($area as $a)
                @php($persen = Format::persen($a['terisi'], $a['total_slot']))
                <a class="kartu-area" data-muncul href="{{ route('parkir', ['jenis' => $a['jenis'], 'area' => $a['id']]) }}">
                    <div class="kartu-area-kepala">
                        <h3>{{ $a['nama'] }}</h3>
                        <span class="kartu-area-panah" aria-hidden="true">→</span>
                    </div>
                    <div class="kartu-area-angka"><strong>{{ $a['kosong'] }}</strong> slot kosong dari {{ $a['total_slot'] }}</div>
                    <div class="bar" aria-label="Terisi {{ $persen }} persen">
                        <div class="bar-jalur"><div class="bar-isi" style="width: {{ $persen }}%"></div></div>
                        <span>{{ $persen }}% terisi</span>
                    </div>
                </a>
            @empty
                <div class="panel kosong-data">Belum ada area parkir. <a href="{{ route('areas.index') }}">Tambah area</a>.</div>
            @endforelse
        </div>
    </section>

    @if ($tarif->isNotEmpty())
        <section class="bagian" aria-labelledby="judul-tarif">
            <div class="bagian-kepala" data-muncul>
                <div>
                    <p class="label">Tarif</p>
                    <h2 id="judul-tarif">Bayar sesuai lama parkir</h2>
                </div>
            </div>
            <div class="tarif-grid">
                @foreach ($tarif as $jenis => $t)
                    @include('partials.kartu-tarif', [
                        'tarif' => $t,
                        'catatan' => 'Contohnya, parkir '.e(Format::durasi($menitContoh)).' cukup bayar <b>'.e(Format::rupiah($contohBiaya[$jenis] ?? null)).'</b>.',
                    ])
                @endforeach
            </div>
        </section>
    @endif

    <section class="bagian" aria-labelledby="judul-cara">
        <div class="bagian-kepala" data-muncul>
            <div>
                <p class="label">Cara kerja</p>
                <h2 id="judul-cara">Tiga langkah, selesai</h2>
            </div>
        </div>
        <div class="cara-grid">
            <div class="cara-item" data-muncul><span>1</span><h3>Booking slot</h3><p>Isi plat, pilih tanggal dan jam, lalu klik slot yang masih bebas di denah.</p></div>
            <div class="cara-item" data-muncul><span>2</span><h3>Datang sesuai jadwal</h3><p>Tunjukkan kode booking ke petugas. Karcis parkir terbit saat check-in.</p></div>
            <div class="cara-item" data-muncul><span>3</span><h3>Bayar saat keluar</h3><p>Biaya dihitung dari waktu masuk sampai keluar, lalu struk terbit.</p></div>
        </div>
    </section>

    <footer class="kaki">
        <span>ParkirPintar · Kelompok 7</span>
        <span>{{ $totalKosong }} dari {{ $totalSlot }} slot kosong saat ini</span>
    </footer>
@endsection


@push('kepala')
    <script>
        // Aktifkan animasi scroll sebelum halaman tampil (mencegah konten berkedip).
        // Tanpa dukungan browser, atau bila "kurangi gerakan" aktif, konten tampil biasa.
        if ('IntersectionObserver' in window && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            document.documentElement.classList.add('js-animasi');
        }
    </script>
@endpush

@push('skrip')
    <script>
        // Animasi scroll: saat scroll turun, elemen [data-muncul] memudar dan naik masuk dari bawah.
        // Saat scroll naik, kebalikannya: elemen yang turun keluar lewat bawah layar memudar dan turun lagi.
        // Elemen yang sudah lewat ke atas layar tetap tampil, jadi tidak ikut beranimasi saat scroll naik.
        (function () {
            if (!document.documentElement.classList.contains('js-animasi')) return;

            const elemen = document.querySelectorAll('[data-muncul]');

            // Kartu dalam satu baris muncul bergantian (jeda 80 ms, paling lama 320 ms).
            elemen.forEach((el) => {
                const saudara = [...el.parentElement.children].filter((x) => x.hasAttribute('data-muncul'));
                el.style.setProperty('--jeda', Math.min(saudara.indexOf(el), 4) * 80 + 'ms');
            });

            const pengamat = new IntersectionObserver((entri) => {
                entri.forEach((e) => {
                    const diBawahLayar = e.boundingClientRect.top > e.rootBounds.height / 2;
                    if (e.intersectionRatio >= 0.15) {
                        e.target.classList.add('tampil');
                    } else if (diBawahLayar) {
                        e.target.classList.remove('tampil');
                    }
                });
            }, { threshold: [0, 0.15] });

            elemen.forEach((el) => pengamat.observe(el));
        })();
    </script>
@endpush
