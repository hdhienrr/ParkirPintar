@extends('layouts.app')

@use('App\Support\Format')
@use('Carbon\Carbon')

@section('label', 'Laporan')
@section('judul', 'Pendapatan')
@section('aksi-judul')
    @include('laporan._tab')
@endsection

@section('isi')
    @include('partials.pesan')

    <div class="statistik">
        <div class="hijau"><span class="label">Total pendapatan</span><strong>{{ Format::rupiah($total['pendapatan']) }}</strong></div>
        <div><span class="label">Transaksi</span><strong>{{ $total['transaksi'] }}</strong></div>
        <div><span class="label">Mobil</span><strong>{{ Format::rupiah($total['mobil']) }}</strong></div>
        <div><span class="label">Motor</span><strong>{{ Format::rupiah($total['motor']) }}</strong></div>
    </div>

    <section class="panel">
        <div class="panel-kepala">
            <h2>Pendapatan per hari</h2>
            <form method="GET" action="{{ route('reports.revenue') }}" class="tab" style="align-items:flex-end; gap:8px">
                <div class="isian">
                    <label for="dari">Dari</label>
                    <input id="dari" name="dari" type="date" value="{{ $dari }}">
                </div>
                <div class="isian">
                    <label for="sampai">Sampai</label>
                    <input id="sampai" name="sampai" type="date" value="{{ $sampai }}">
                </div>
                <button class="tombol">Terapkan</button>
            </form>
        </div>
        <div class="tabel-bungkus">
            <table>
                <thead>
                <tr>
                    <th>Tanggal</th>
                    <th class="kanan">Transaksi</th>
                    <th class="kanan">Mobil</th>
                    <th class="kanan">Motor</th>
                    <th class="kanan">Pendapatan</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($harian as $h)
                    <tr>
                        <td class="angka">{{ Carbon::parse($h['tanggal'])->locale('id')->translatedFormat('D, d M Y') }}</td>
                        <td class="kanan angka">{{ $h['transaksi'] }}</td>
                        <td class="kanan angka">{{ Format::rupiah($h['mobil']) }}</td>
                        <td class="kanan angka">{{ Format::rupiah($h['motor']) }}</td>
                        <td class="kanan angka">{{ Format::rupiah($h['pendapatan']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="kosong-data">Belum ada kendaraan yang check-out pada rentang tanggal ini.</td></tr>
                @endforelse
                </tbody>
                @if ($harian->isNotEmpty())
                    <tfoot>
                    <tr>
                        <td>Total</td>
                        <td class="kanan angka">{{ $total['transaksi'] }}</td>
                        <td class="kanan angka">{{ Format::rupiah($total['mobil']) }}</td>
                        <td class="kanan angka">{{ Format::rupiah($total['motor']) }}</td>
                        <td class="kanan angka">{{ Format::rupiah($total['pendapatan']) }}</td>
                    </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </section>
@endsection
