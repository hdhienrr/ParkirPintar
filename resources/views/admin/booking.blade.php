@extends('layouts.app')

@use('App\Support\Format')

@section('label', 'Admin')
@section('judul', 'Semua booking')

@section('aksi-judul')
    @include('admin._tab-booking')
@endsection

@section('isi')
    @include('partials.pesan')

    <section class="panel">
        <div class="panel-kepala">
            <h2>{{ Format::tanggal($tanggal) }} · {{ $booking->count() }} booking</h2>
            <form method="GET" action="{{ route('admin.booking.index') }}" class="tab filter-baris">
                <div class="isian">
                    <label for="tanggal">Tanggal</label>
                    <input id="tanggal" name="tanggal" type="date" value="{{ $tanggal->toDateString() }}">
                </div>
                <div class="isian">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="">Semua</option>
                        @foreach ($semuaStatus as $s)
                            <option value="{{ $s->value }}" @selected($statusDipilih === $s)>{{ $s->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="tombol">Terapkan</button>
            </form>
        </div>

        <div class="tabel-bungkus">
            <table>
                <thead>
                <tr>
                    <th>Kode</th>
                    <th>Jam</th>
                    <th>Pemesan</th>
                    <th>Plat</th>
                    <th>Slot</th>
                    <th>Status</th>
                    <th class="kanan">Aksi</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($booking as $b)
                    <tr @class(['baris-merah' => $b->melewati_waktu])>
                        <td><a href="{{ route('booking.show', $b) }}" class="mono">{{ $b->kode_booking }}</a></td>
                        <td class="angka">{{ Format::jam($b->waktu_mulai) }}–{{ Format::jam($b->waktu_selesai) }}</td>
                        <td>{{ $b->user->name }}</td>
                        <td class="mono">{{ $b->plat }}</td>
                        <td><span class="mono">{{ $b->slot->kode_slot }}</span> <span class="redup">{{ $b->slot->area->nama }}</span></td>
                        <td>
                            <span class="status-booking status-{{ $b->status->value }}">{{ $b->status->label() }}</span>
                            @if ($b->melewati_waktu)
                                <span class="label-merah">Melewati waktu</span>
                            @endif
                        </td>
                        <td class="kanan">
                            <div class="aksi-baris">
                                @if ($b->kendaraan?->nomor_karcis)
                                    <a href="{{ route('karcis.show', $b->kendaraan) }}" class="tombol tombol-kecil">Karcis</a>
                                @endif
                                @can('pindah', $b)
                                    <a href="{{ route('admin.booking.pindah.form', $b) }}" class="tombol tombol-kecil">Pindah</a>
                                @endcan
                                @can('tandaiKeluar', $b)
                                    <form method="POST" action="{{ route('admin.booking.keluar', $b) }}">
                                        @csrf
                                        <button class="tombol tombol-kecil tombol-gelap">Tandai keluar</button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="kosong-data">Belum ada booking pada tanggal ini.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
