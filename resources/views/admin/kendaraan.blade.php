@extends('layouts.app')

@use('App\Support\Format')

@section('label', 'Admin')
@section('judul', 'Kendaraan sedang parkir')

@section('isi')
    @include('partials.pesan')

    <section class="panel">
        <div class="panel-kepala">
            <h2>{{ $daftar->count() }} kendaraan di dalam</h2>
            <span class="label" style="margin:0">Biaya dihitung bila keluar sekarang</span>
        </div>

        <div class="tabel-bungkus">
            <table>
                <thead>
                <tr>
                    <th>Karcis</th>
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
                            @if ($k->nomor_karcis)
                                <a href="{{ route('karcis.show', $k) }}" class="mono">{{ $k->nomor_karcis }}</a>
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
                            <form method="POST" action="{{ route('checkout', $k->id) }}" onsubmit="return confirm('Check-out {{ $k->plat }}?')">
                                @csrf
                                <button class="tombol tombol-kecil tombol-gelap">Check-out</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="kosong-data">Tidak ada kendaraan di dalam.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
