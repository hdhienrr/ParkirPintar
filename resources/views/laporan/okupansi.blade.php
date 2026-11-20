@extends('layouts.app')

@use('App\Support\Format')

@section('label', 'Laporan')
@section('judul', 'Okupansi')
@section('aksi-judul')
    @include('laporan._tab')
@endsection

@section('isi')
    <div class="statistik">
        <div><span class="label">Total slot</span><strong>{{ $total['total_slot'] }}</strong></div>
        <div class="merah"><span class="label">Terisi</span><strong>{{ $total['terisi'] }}</strong></div>
        <div class="hijau"><span class="label">Kosong</span><strong>{{ $total['kosong'] }}</strong></div>
        @foreach ($per_jenis as $jenis => $j)
            <div>
                <span class="label">{{ $jenis }} terisi</span>
                <strong>{{ $j['terisi'] }}<span class="redup" style="font-size:14px">/{{ $j['total_slot'] }}</span></strong>
            </div>
        @endforeach
    </div>

    <section class="panel">
        <div class="panel-kepala">
            <h2>Okupansi per area</h2>
            <span class="label" style="margin:0">Per {{ now()->format('d M Y H:i') }}</span>
        </div>
        <div class="tabel-bungkus">
            <table>
                <thead>
                <tr>
                    <th>Area</th>
                    <th>Jenis</th>
                    <th class="kanan">Slot</th>
                    <th class="kanan">Terisi</th>
                    <th class="kanan">Kosong</th>
                    <th>Okupansi</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($area as $a)
                    @php($persen = Format::persen($a['terisi'], $a['total_slot']))
                    <tr>
                        <td>{{ $a['nama'] }}</td>
                        <td><span class="lencana lencana-{{ $a['jenis'] }}">{{ $a['jenis'] }}</span></td>
                        <td class="kanan angka">{{ $a['total_slot'] }}</td>
                        <td class="kanan angka">{{ $a['terisi'] }}</td>
                        <td class="kanan angka">{{ $a['kosong'] }}</td>
                        <td>
                            <div class="bar" aria-label="Okupansi {{ $persen }} persen">
                                <div class="bar-jalur"><div class="bar-isi" style="width: {{ $persen }}%"></div></div>
                                <span>{{ $persen }}%</span>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="kosong-data">Belum ada area.</td></tr>
                @endforelse
                </tbody>
                <tfoot>
                @foreach ($per_jenis as $jenis => $j)
                    <tr>
                        <td colspan="2">Subtotal {{ $jenis }}</td>
                        <td class="kanan angka">{{ $j['total_slot'] }}</td>
                        <td class="kanan angka">{{ $j['terisi'] }}</td>
                        <td class="kanan angka">{{ $j['kosong'] }}</td>
                        <td class="angka">{{ Format::persen($j['terisi'], $j['total_slot']) }}%</td>
                    </tr>
                @endforeach
                <tr>
                    <td colspan="2">Total</td>
                    <td class="kanan angka">{{ $total['total_slot'] }}</td>
                    <td class="kanan angka">{{ $total['terisi'] }}</td>
                    <td class="kanan angka">{{ $total['kosong'] }}</td>
                    <td class="angka">{{ Format::persen($total['terisi'], $total['total_slot']) }}%</td>
                </tr>
                </tfoot>
            </table>
        </div>
    </section>
@endsection
