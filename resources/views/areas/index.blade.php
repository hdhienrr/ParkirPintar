@extends('layouts.app')

@use('App\Support\Format')

@section('label', 'Data master')
@section('judul', 'Area parkir')

@section('isi')
    @php
        $totalSlot = $area->sum('total_slot');
        $totalTerisi = $area->sum('terisi');
        $slotPerZona = \App\Models\SlotParkir::SLOT_PER_ZONA;
    @endphp

    <div class="statistik">
        <div><span class="label">Area</span><strong>{{ $area->count() }}</strong></div>
        <div><span class="label">Total slot</span><strong>{{ $totalSlot }}</strong></div>
        <div class="merah"><span class="label">Terisi</span><strong>{{ $totalTerisi }}</strong></div>
        <div class="hijau"><span class="label">Kosong</span><strong>{{ $totalSlot - $totalTerisi }}</strong></div>
    </div>

    <div class="dua-kolom">
        <section class="panel">
            <div class="panel-kepala">
                <h2>Daftar area</h2>
                <span class="label" style="margin:0">Satu area = satu lantai untuk satu jenis kendaraan</span>
            </div>
            <div class="tabel-bungkus">
                <table>
                    <thead>
                    <tr>
                        <th>Area</th>
                        <th>Jenis</th>
                        <th class="kanan">Lantai</th>
                        <th class="kanan">Slot</th>
                        <th class="kanan">Terisi</th>
                        <th class="kanan">Kosong</th>
                        <th>Okupansi</th>
                        <th class="kanan">Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($area as $a)
                        @php($persen = Format::persen($a['terisi'], $a['total_slot']))
                        <tr>
                            <td>{{ $a['nama'] }}</td>
                            <td><span class="lencana lencana-{{ $a['jenis'] }}">{{ $a['jenis'] }}</span></td>
                            <td class="kanan angka">{{ $a['lantai'] }}</td>
                            <td class="kanan angka">{{ $a['total_slot'] }}</td>
                            <td class="kanan angka">{{ $a['terisi'] }}</td>
                            <td class="kanan angka">{{ $a['kosong'] }}</td>
                            <td>
                                <div class="bar" aria-label="Okupansi {{ $persen }} persen">
                                    <div class="bar-jalur"><div class="bar-isi" style="width: {{ $persen }}%"></div></div>
                                    <span>{{ $persen }}%</span>
                                </div>
                            </td>
                            <td>
                                <div class="aksi-baris">
                                    <a class="tombol tombol-kecil" href="{{ route('parkir', ['jenis' => $a['jenis'], 'area' => $a['id']]) }}">Denah</a>
                                    <a class="tombol tombol-kecil" href="{{ route('areas.index', ['ubah' => $a['id']]) }}">Ubah</a>
                                    <form method="POST" action="{{ route('areas.destroy', $a['id']) }}" data-konfirmasi="Hapus {{ $a['nama'] }} beserta semua slotnya?" onsubmit="return confirm(this.dataset.konfirmasi)">
                                        @csrf
                                        @method('DELETE')
                                        <button class="tombol tombol-kecil tombol-bahaya">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="kosong-data">Belum ada area.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <aside class="panel samping">
            <div class="panel-kepala">
                <h2>{{ $diubah ? 'Ubah area' : 'Tambah area' }}</h2>
                @if ($diubah)
                    <a class="tombol tombol-kecil" href="{{ route('areas.index') }}">Batal</a>
                @endif
            </div>
            <div class="panel-isi">
                @include('partials.pesan')

                @if ($diubah)
                    <form method="POST" action="{{ route('areas.update', $diubah['id']) }}" class="kolom-form">
                        @csrf
                        @method('PUT')
                        <dl class="rincian">
                            <dt>Jenis</dt><dd>{{ $diubah['jenis'] }}</dd>
                            <dt>Lantai</dt><dd>{{ $diubah['lantai'] }}</dd>
                        </dl>
                        <div class="isian">
                            <label for="nama">Nama area</label>
                            <input id="nama" name="nama" value="{{ old('nama', $diubah['nama']) }}" maxlength="100" required>
                        </div>
                        <div class="isian">
                            <label for="jumlah_slot">Jumlah slot</label>
                            <input id="jumlah_slot" name="jumlah_slot" type="number" min="{{ $slotPerZona }}" max="{{ config('parkir.area.slot_maks') }}" step="{{ $slotPerZona }}" value="{{ old('jumlah_slot', $diubah['total_slot']) }}" required>
                            <span class="petunjuk">Kelipatan {{ $slotPerZona }} (satu zona). Slot yang pernah dipakai tidak bisa dikurangi.</span>
                        </div>
                        <button class="tombol tombol-utama tombol-penuh">Simpan perubahan</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('areas.store') }}" class="kolom-form">
                        @csrf
                        <div class="isian">
                            <label for="nama">Nama area</label>
                            <input id="nama" name="nama" value="{{ old('nama') }}" maxlength="100" placeholder="Mobil Lantai 4" required>
                        </div>
                        <div class="isian">
                            <label for="jenis">Jenis kendaraan</label>
                            <select id="jenis" name="jenis" required>
                                @foreach (\App\Enums\JenisKendaraan::cases() as $j)
                                    <option value="{{ $j->value }}" @selected(old('jenis') === $j->value)>{{ $j->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="isian">
                            <label for="lantai">Lantai</label>
                            <input id="lantai" name="lantai" type="number" min="1" max="{{ config('parkir.area.lantai_maks') }}" value="{{ old('lantai') }}" required>
                        </div>
                        <div class="isian">
                            <label for="jumlah_slot">Jumlah slot</label>
                            <input id="jumlah_slot" name="jumlah_slot" type="number" min="{{ $slotPerZona }}" max="{{ config('parkir.area.slot_maks') }}" step="{{ $slotPerZona }}" value="{{ old('jumlah_slot', config('parkir.area.slot_bawaan')) }}" required>
                            <span class="petunjuk">Kelipatan {{ $slotPerZona }} (satu zona = {{ \App\Models\SlotParkir::BLOK_PER_ZONA }} blok × {{ \App\Models\SlotParkir::SLOT_PER_BLOK }} slot). {{ config('parkir.area.slot_bawaan') }} = satu lantai standar.</span>
                        </div>
                        <button class="tombol tombol-utama tombol-penuh">Tambah area</button>
                    </form>
                @endif
            </div>
        </aside>
    </div>
@endsection
