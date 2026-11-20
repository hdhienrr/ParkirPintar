@extends('layouts.app')

@use('App\Support\Format')
@use('App\Enums\StatusBooking')

@section('label', 'Booking')
@section('judul', 'Booking saya')

@section('aksi-judul')
    <a href="{{ route('booking.create') }}" class="tombol tombol-utama">Booking baru</a>
@endsection

@section('isi')
    @include('partials.pesan')

    @forelse ($booking as $b)
        <article class="panel kartu-booking">
            <div class="panel-isi">
                <div class="kartu-booking-kepala">
                    <div>
                        <a href="{{ route('booking.show', $b) }}" class="mono kode-booking">{{ $b->kode_booking }}</a>
                        <div class="redup">{{ Format::tanggal($b->waktu_mulai) }} · {{ Format::jam($b->waktu_mulai) }}–{{ Format::jam($b->waktu_selesai) }}</div>
                    </div>
                    <span class="status-booking status-{{ $b->status->value }}">{{ $b->status->label() }}</span>
                </div>

                <dl class="rincian rincian-baris">
                    <dt>Plat</dt><dd class="mono">{{ $b->plat }}</dd>
                    <dt>Lantai</dt><dd>{{ $b->slot->area->nama }}</dd>
                    <dt>Slot</dt><dd class="mono">{{ $b->slot->kode_slot }}</dd>
                </dl>

                @if ($b->melewati_waktu)
                    <p class="pesan pesan-gagal" role="alert">
                        <span class="pesan-ikon" aria-hidden="true">!</span>
                        Waktu booking sudah habis sejak {{ Format::jam($b->waktu_selesai) }}. Tarif tetap berjalan sampai keluar, dan bisa kena denda bila booking berikutnya batal karena slot belum kosong.
                    </p>
                @endif

                @if ($b->status === StatusBooking::DibatalkanSistem && $b->alasan)
                    <p class="redup">Alasan: {{ $b->alasan }}</p>
                @endif

                <div class="kartu-booking-aksi">
                    <a href="{{ route('booking.show', $b) }}" class="tombol tombol-kecil">Lihat bukti</a>
                    @if ($b->kendaraan?->nomor_karcis)
                        <a href="{{ route('karcis.show', $b->kendaraan) }}" class="tombol tombol-kecil">Karcis</a>
                    @endif
                    @if ($b->bisaDibatalkan())
                        <form method="POST" action="{{ route('booking.batal', $b) }}" onsubmit="return confirm('Batalkan booking {{ $b->kode_booking }}?')">
                            @csrf
                            <button class="tombol tombol-kecil tombol-bahaya">Batalkan</button>
                        </form>
                    @endif
                </div>
            </div>
        </article>
    @empty
        <div class="panel kosong-data">
            Belum ada booking. <a href="{{ route('booking.create') }}">Buat booking pertama</a>.
        </div>
    @endforelse
@endsection
