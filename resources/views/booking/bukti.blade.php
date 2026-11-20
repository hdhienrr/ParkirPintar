@extends('layouts.app')

@use('App\Support\Format')

@section('label', 'Booking')
@section('judul', 'Bukti booking')

@section('aksi-judul')
    <div class="aksi-cetak">
        @can('booking')
            <a href="{{ route('booking.index') }}" class="tombol">Booking saya</a>
        @endcan
        <button type="button" class="tombol tombol-utama" onclick="window.print()">Cetak</button>
    </div>
@endsection

@section('isi')
    @include('partials.pesan')

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

        <footer class="struk-kaki">
            Datang paling cepat {{ config('parkir.booking.toleransi_awal_menit') }} menit sebelum jam mulai
            dan paling lambat {{ config('parkir.booking.toleransi_terlambat_menit') }} menit sesudahnya.
            Tunjukkan kode booking ke petugas.
        </footer>
    </article>
@endsection
