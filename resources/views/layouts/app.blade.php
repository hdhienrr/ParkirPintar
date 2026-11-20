<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('judul') · ParkirPintar</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@500;600&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/parkirpintar.css') }}?v=25">
    @stack('kepala')
</head>
<body class="@yield('kelas-body')">
<header class="topbar">
    <a href="{{ route('beranda') }}" class="brand" aria-label="ParkirPintar, beranda">
        <span class="brand-logo" aria-hidden="true">P</span>
        <span>ParkirPintar</span>
    </a>
    <nav class="nav" aria-label="Menu utama">
        @php($rute = request()->route()?->getName() ?? '')
        @php($jenisAktif = request()->route('jenis'))
        <a href="{{ route('beranda') }}" @class(['aktif' => $rute === 'beranda'])>Beranda</a>
        <a href="{{ route('parkir', 'mobil') }}" @class(['aktif' => $rute === 'parkir' && $jenisAktif === 'mobil'])>Parkir Mobil</a>
        <a href="{{ route('parkir', 'motor') }}" @class(['aktif' => $rute === 'parkir' && $jenisAktif === 'motor'])>Parkir Motor</a>
        <a href="{{ route('areas.index') }}" @class(['aktif' => str_starts_with($rute, 'areas.')])>Area</a>
        <a href="{{ route('reports.occupancy') }}" @class(['aktif' => str_starts_with($rute, 'reports.')])>Laporan</a>
    </nav>
    <div class="topbar-kanan">
        <span class="jam" id="jam" aria-label="Waktu sekarang">{{ now()->format('d M Y, H:i') }}</span>
        <a href="{{ route('parkir', 'mobil') }}" class="tombol tombol-terang tombol-topbar">Mulai check-in</a>
    </div>
</header>

@hasSection('penuh')
    @yield('penuh')
@else
    <main class="konten">
        <div class="judul-halaman">
            <div>
                <p class="label">@yield('label')</p>
                <h1>@yield('judul')</h1>
            </div>
            @yield('aksi-judul')
        </div>

        @yield('isi')
    </main>
@endif

<script>
    (function () {
        const jam = document.getElementById('jam');
        const format = new Intl.DateTimeFormat('id-ID', {
            day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', timeZone: 'Asia/Jakarta',
        });
        const tulis = () => { jam.textContent = format.format(new Date()).replace(/\./g, ':'); };
        tulis();
        setInterval(tulis, 30000);

        // Navbar memendek dan berbayang setelah halaman digulir.
        const topbar = document.querySelector('.topbar');
        const cekGulir = () => topbar.classList.toggle('tergulir', window.scrollY > 10);
        window.addEventListener('scroll', cekGulir, { passive: true });
        cekGulir();
    })();
</script>
@stack('skrip')
</body>
</html>
