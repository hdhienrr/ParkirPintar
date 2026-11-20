@extends('layouts.app')

@use('App\Support\Format')

@section('label', 'Booking')
@section('judul', 'Bukti booking')

@section('aksi-judul')
    <div class="aksi-cetak">
        @can('booking')
            <a href="{{ route('booking.index') }}" class="tombol">Booking saya</a>
        @endcan
        @if ($booking->kendaraan?->nomor_karcis)
            <a href="{{ route('karcis.show', $booking->kendaraan) }}" class="tombol">Karcis dan struk</a>
        @endif
        @can('tandaiKeluar', $booking)
            <form method="POST" action="{{ route('admin.booking.keluar', $booking) }}">
                @csrf
                <button class="tombol tombol-gelap">Tandai keluar</button>
            </form>
        @endcan
        <button type="button" class="tombol tombol-utama" onclick="window.print()">Cetak</button>
    </div>
@endsection

@section('isi')
    @include('partials.pesan')

    <div class="struk-grup">
        <article class="struk" aria-label="Bukti booking {{ $booking->kode_booking }}">
            <header class="struk-kepala">
                <strong>ParkirPintar</strong>
                <span>Bukti booking</span>
            </header>

            <div class="struk-kode mono">{{ $booking->kode_booking }}</div>
            <div class="struk-status">{{ $booking->status->label() }}</div>

            <dl class="struk-rincian">
                <dt>Nama</dt><dd>{{ $booking->user->name }}</dd>
                <dt>Plat</dt><dd class="mono">{{ $booking->plat }}</dd>
                <dt>Jenis</dt><dd>{{ ucfirst($booking->jenis) }}</dd>
                <dt>Lantai</dt><dd>{{ $booking->slot->area->nama }}</dd>
                <dt>Slot</dt><dd class="mono">{{ $booking->slot->kode_slot }}</dd>
                <dt>Tanggal</dt><dd>{{ Format::tanggal($booking->waktu_mulai) }}</dd>
                <dt>Jam</dt><dd>{{ Format::jam($booking->waktu_mulai) }} – {{ Format::jam($booking->waktu_selesai) }}</dd>
            </dl>

            @if ($booking->alasan)
                <p class="struk-kaki">Dibatalkan sistem: {{ $booking->alasan }}</p>
            @endif

            <footer class="struk-kaki">
                Datang paling cepat {{ config('parkir.booking.toleransi_awal_menit') }} menit sebelum jam mulai
                dan paling lambat {{ config('parkir.booking.toleransi_terlambat_menit') }} menit sesudahnya.
                Tunjukkan kode booking ke petugas.
            </footer>
        </article>

        {{-- Booking yang dibatalkan sistem tidak punya karcis, jadi struk keluarnya ada di sini. --}}
        @if ($booking->waktu_keluar)
            <article class="struk" aria-label="Struk keluar {{ $booking->kode_booking }}">
                <header class="struk-kepala">
                    <strong>ParkirPintar</strong>
                    <span>Struk keluar</span>
                </header>

                <dl class="struk-rincian">
                    <dt>Kode booking</dt><dd class="mono">{{ $booking->kode_booking }}</dd>
                    <dt>Keluar</dt><dd>{{ Format::tanggal($booking->waktu_keluar) }}, {{ Format::jam($booking->waktu_keluar) }}</dd>
                    <dt>Biaya parkir</dt><dd>{{ Format::rupiah(0) }}</dd>
                    @if ($booking->denda > 0)
                        <dt>Denda keluar terlambat</dt><dd>{{ Format::rupiah($booking->denda) }}</dd>
                    @endif
                    <dt>Total</dt><dd class="besar">{{ Format::rupiah($booking->denda) }}</dd>
                </dl>

                <footer class="struk-kaki">Booking dibatalkan sistem tanpa biaya pembatalan.</footer>
            </article>
        @endif
    </div>
@endsection
