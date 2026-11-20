@extends('layouts.app')

@use('App\Support\Format')
@use('App\Enums\StatusBooking')

@section('label', 'Admin')
@section('judul', 'Pindahkan booking')

@section('isi')
    <div class="panel kartu-akun">
        <div class="panel-isi">
            @include('partials.pesan')

            <dl class="rincian">
                <dt>Kode</dt><dd class="mono">{{ $booking->kode_booking }}</dd>
                <dt>Plat</dt><dd class="mono">{{ $booking->plat }}</dd>
                <dt>Jam</dt><dd>{{ Format::jam($booking->waktu_mulai) }}–{{ Format::jam($booking->waktu_selesai) }}</dd>
                <dt>Slot sekarang</dt><dd class="mono">{{ $booking->slot->kode_slot }} · {{ $booking->slot->area->nama }}</dd>
                <dt>Status</dt><dd>{{ $booking->status->label() }}</dd>
            </dl>

            @if ($booking->status === StatusBooking::Menunggu)
                <p class="redup">Kendaraan sudah tiba, jadi setelah dipindah langsung check-in di slot baru tanpa denda.</p>
            @endif

            @if ($slotPengganti->isEmpty())
                <div class="kosong-data">Tidak ada slot {{ $booking->jenis }} lain yang bebas pada jam ini.</div>
            @else
                <form method="POST" action="{{ route('admin.booking.pindah', $booking) }}" class="kolom-form">
                    @csrf
                    <div class="isian">
                        <label for="slot_id">Slot pengganti</label>
                        <select id="slot_id" name="slot_id" required>
                            @foreach ($slotPengganti->groupBy(fn ($s) => $s->area->nama) as $namaArea => $slot)
                                <optgroup label="{{ $namaArea }}">
                                    @foreach ($slot as $s)
                                        <option value="{{ $s->id }}">{{ $s->kode_slot }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <span class="petunjuk">Slot di lantai yang sama ditampilkan lebih dulu.</span>
                    </div>
                    <button class="tombol tombol-utama tombol-penuh">Pindahkan</button>
                </form>
            @endif

            <p class="kartu-akun-catatan"><a href="{{ route('admin.booking.index', ['tanggal' => $booking->waktu_mulai->toDateString()]) }}">Kembali ke daftar booking</a></p>
        </div>
    </div>
@endsection
