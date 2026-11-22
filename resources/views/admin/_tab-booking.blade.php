<nav class="tab" aria-label="Tampilan booking">
    <a href="{{ route('admin.booking.index') }}" @class(['aktif' => request()->routeIs('admin.booking.index')])>Per tanggal</a>
    <a href="{{ route('admin.booking.parkir') }}" @class(['aktif' => request()->routeIs('admin.booking.parkir')])>Sedang parkir</a>
</nav>
