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
            <h2>{{ $daftar->count() }} kendaraan sedang parkir</h2>
            <span class="label" style="margin:0">Dari tanggal mana pun · biaya bila keluar sekarang</span>
        </div>

        <div class="tabel-bungkus">
            <table>
                <thead>
                <tr>
                    <th>Kode booking</th>
                    <th>Plat</th>
                    <th>Slot</th>
                    <th>Masuk</th>
                    <th>Booking sampai</th>
                    <th class="kanan">Durasi</th>
                    <th class="kanan">Perkiraan biaya</th>
                    <th class="kanan">Aksi</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($daftar as $baris)
                    @php($k = $baris['kendaraan'])
                    <tr @class(['baris-merah' => $baris['melewati_waktu']])>
                        <td>
                            @if ($k->booking)
                                <a href="{{ route('booking.show', $k->booking) }}" class="mono">{{ $k->booking->kode_booking }}</a>
                            @else
                                <span class="redup">tanpa booking</span>
                            @endif
                        </td>
                        <td class="mono">{{ $k->plat }}</td>
                        <td><span class="mono">{{ $k->slot->kode_slot }}</span> <span class="redup">{{ $k->slot->area->nama }}</span></td>
                        <td class="angka">{{ Format::waktuSingkat($k->waktu_masuk) }}</td>
                        <td class="angka">
                            {{ $k->booking ? Format::waktuSingkat($k->booking->waktu_selesai) : '-' }}
                            @if ($baris['melewati_waktu'])
                                <span class="label-merah">Melewati waktu</span>
                            @endif
                        </td>
                        <td class="kanan angka">{{ $baris['perkiraan'] ? Format::durasi($baris['perkiraan']['durasi_menit']) : '-' }}</td>
                        <td class="kanan angka">{{ Format::rupiah($baris['perkiraan']['biaya'] ?? null) }}</td>
                        <td class="kanan">
                            @if ($k->nomor_karcis)
                                <a href="{{ route('karcis.show', $k) }}" class="tombol tombol-kecil">Karcis</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="kosong-data">Tidak ada kendaraan yang sedang parkir.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
