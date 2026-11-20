<nav class="tab" aria-label="Jenis laporan">
    <a href="{{ route('reports.occupancy') }}" @class(['aktif' => request()->routeIs('reports.occupancy')])>Okupansi</a>
    <a href="{{ route('reports.revenue') }}" @class(['aktif' => request()->routeIs('reports.revenue')])>Pendapatan</a>
</nav>
