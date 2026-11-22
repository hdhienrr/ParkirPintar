@extends('layouts.app')

@section('label', 'Booking')
@section('judul', 'Booking slot parkir')

@section('aksi-judul')
    <div class="legenda" aria-label="Keterangan status slot">
        <span><i class="l-kosong" aria-hidden="true">✓</i>Bebas</span>
        <span><i class="l-terisi" aria-hidden="true">✕</i>Sudah dibooking</span>
        <span><i class="l-dipilih" aria-hidden="true">↓</i>Dipilih</span>
    </div>
@endsection

@section('isi')
    <div
        id="halaman-booking"
        class="dua-kolom"
        data-url-ketersediaan="{{ route('booking.ketersediaan') }}"
        data-gambar-mobil="{{ asset(\App\Enums\JenisKendaraan::Mobil->gambar()) }}"
        data-gambar-motor="{{ asset(\App\Enums\JenisKendaraan::Motor->gambar()) }}"
        data-jam-tutup="{{ $aturan['jam_tutup'] }}"
        data-slot-lama="{{ old('slot_id') }}"
    >
        <section class="panel" aria-labelledby="judul-denah">
            <div class="panel-kepala">
                <div id="tab-lantai" class="tab" role="tablist" aria-label="Pilih lantai"></div>
                <h2 id="judul-denah" class="label" style="margin:0"><span id="ringkasan-lantai"></span></h2>
            </div>
            <div class="denah-gulir">
                <div id="denah" class="denah" aria-live="polite">
                    <div class="denah-status">Pilih jenis kendaraan (Mobil atau Motor) di samping untuk melihat denah.</div>
                </div>
            </div>
        </section>

        <aside class="panel samping" aria-label="Formulir booking">
            <div class="panel-isi">
                <div id="pesan" aria-live="assertive">@include('partials.pesan')</div>

                <form id="form-booking" method="POST" action="{{ route('booking.store') }}" class="langkah">
                    @csrf
                    <input type="hidden" name="slot_id" id="slot_id" value="{{ old('slot_id') }}">

                    <div class="langkah-item">
                        <span class="langkah-nomor">1</span>
                        <div class="kolom-form">
                            <div class="langkah-judul">Data kendaraan</div>
                            <div class="isian">
                                <label for="plat">Plat nomor</label>
                                <input id="plat" name="plat" class="input-plat" maxlength="15" autocomplete="off" placeholder="B 1234 XY" value="{{ old('plat') }}" required>
                            </div>
                            {{-- Belum ada pilihan bawaan: denah baru dimuat setelah jenis dipilih. --}}
                            <fieldset class="isian pilih-jenis">
                                <legend>Jenis kendaraan</legend>
                                <div class="pilih-jenis-opsi">
                                    @foreach ($jenisKendaraan as $j)
                                        <label>
                                            <input type="radio" name="jenis" value="{{ $j->value }}" @checked(old('jenis') === $j->value) required>
                                            <span>{{ $j->label() }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                        </div>
                    </div>

                    <div class="langkah-item">
                        <span class="langkah-nomor">2</span>
                        <div class="kolom-form">
                            <div class="langkah-judul">Jadwal</div>
                            <div class="isian">
                                <label for="tanggal">Tanggal</label>
                                <input id="tanggal" name="tanggal" type="date" min="{{ $tanggalMin }}" max="{{ $tanggalMaks }}" value="{{ old('tanggal', $tanggalAwal) }}" required>
                            </div>
                            <div class="isian-ganda">
                                <div class="isian">
                                    <label for="jam-mulai">Jam mulai</label>
                                    <select id="jam-mulai" name="jam" required>
                                        @for ($jam = $aturan['jam_buka']; $jam < $aturan['jam_tutup']; $jam++)
                                            <option value="{{ $jam }}" @selected((int) old('jam', $jamAwal) === $jam)>{{ sprintf('%02d.00', $jam) }}</option>
                                        @endfor
                                    </select>
                                </div>
                                <div class="isian">
                                    <label for="durasi">Durasi</label>
                                    <select id="durasi" name="durasi" required>
                                        @for ($d = $aturan['durasi_min_jam']; $d <= $aturan['durasi_maks_jam']; $d++)
                                            <option value="{{ $d }}" @selected((int) old('durasi', 2) === $d)>{{ $d }} jam</option>
                                        @endfor
                                    </select>
                                </div>
                            </div>
                            <span class="petunjuk">
                                Buka {{ sprintf('%02d.00', $aturan['jam_buka']) }} sampai {{ sprintf('%02d.00', $aturan['jam_tutup']) }},
                                paling jauh {{ $aturan['hari_ke_depan_maks'] }} hari ke depan.
                            </span>
                        </div>
                    </div>

                    <div class="langkah-item">
                        <span class="langkah-nomor">3</span>
                        <div>
                            <div class="langkah-judul">Pilih slot</div>
                            <div id="info-slot" class="kotak-info">Klik slot bebas di denah.</div>
                        </div>
                    </div>

                    <div class="langkah-item">
                        <span class="langkah-nomor">4</span>
                        <div>
                            <div class="langkah-judul">Konfirmasi</div>
                            <button type="submit" id="tombol-booking" class="tombol tombol-utama tombol-penuh" disabled>Booking</button>
                        </div>
                    </div>
                </form>

                <div class="info-tarif">
                    Booking gratis. Biaya parkir dihitung dari waktu masuk sampai keluar.
                    <a href="{{ route('peraturan') }}">Baca peraturan</a>.
                </div>
            </div>
        </aside>
    </div>
@endsection

@push('skrip')
    <script src="{{ asset('js/denah.js') }}?v=1" defer></script>
    <script src="{{ asset('js/booking.js') }}?v=4" defer></script>
@endpush
