@extends('layouts.app')

@php($namaJenis = $jenis->label())

@section('label', 'Check-in & check-out')
@section('judul', "Parkir {$namaJenis}")

@section('aksi-judul')
    <div class="legenda" aria-label="Keterangan status slot">
        <span><i class="l-kosong" aria-hidden="true">✓</i>Kosong</span>
        <span><i class="l-terisi" aria-hidden="true">✕</i>Terisi</span>
        <span><i class="l-dipilih" aria-hidden="true">↓</i>Dipilih</span>
    </div>
@endsection

@section('isi')
    @if ($area->isEmpty())
        <div class="panel kosong-data">
            Belum ada area untuk {{ $jenis->value }}. <a href="{{ route('areas.index') }}">Tambah area</a> dulu.
        </div>
    @else
        <div
            id="halaman-parkir"
            class="dua-kolom"
            data-jenis="{{ $jenis->value }}"
            data-dasar="{{ url('/') }}"
            data-area="{{ $areaDipilih['id'] }}"
            data-gambar="{{ asset($jenis->gambar()) }}"
            data-segar-detik="{{ config('parkir.denah.segar_detik') }}"
        >
            <section class="panel" aria-labelledby="judul-denah">
                <div class="panel-kepala">
                    <div class="tab" role="tablist" aria-label="Pilih lantai">
                        @foreach ($area as $a)
                            <button
                                type="button"
                                role="tab"
                                data-tab-area="{{ $a['id'] }}"
                                aria-selected="{{ $a['id'] === $areaDipilih['id'] ? 'true' : 'false' }}"
                            >
                                {{ $a['nama'] }}<span class="hitung" data-hitung-area="{{ $a['id'] }}">{{ $a['kosong'] }}/{{ $a['total_slot'] }}</span>
                            </button>
                        @endforeach
                    </div>
                    <h2 id="judul-denah" class="label" style="margin:0">
                        <span id="ringkasan-area">{{ $areaDipilih['kosong'] }} kosong · {{ $areaDipilih['terisi'] }} terisi</span>
                    </h2>
                </div>
                <div class="denah-gulir">
                    <div id="denah" class="denah" aria-live="polite">
                        <div class="denah-status">Memuat denah…</div>
                    </div>
                </div>
            </section>

            <aside class="panel samping" aria-label="Formulir parkir">
                <div class="panel-isi">
                    <div id="pesan" aria-live="assertive"></div>

                    {{-- Mode check-in: slot kosong dipilih atau belum ada pilihan --}}
                    <form id="mode-checkin" class="langkah" novalidate>
                        <div class="langkah-item" id="langkah-1">
                            <span class="langkah-nomor">1</span>
                            <div>
                                <div class="langkah-judul">Data kendaraan</div>
                                <div class="isian">
                                    <label for="plat">Plat nomor</label>
                                    <input id="plat" name="plat" class="input-plat" maxlength="15" autocomplete="off" placeholder="B 1234 XY" required>
                                </div>
                            </div>
                        </div>

                        <div class="langkah-item" id="langkah-2">
                            <span class="langkah-nomor">2</span>
                            <div>
                                <div class="langkah-judul">Pilih lantai &amp; slot</div>
                                <div id="info-slot" class="kotak-info">Klik slot hijau di denah.</div>
                            </div>
                        </div>

                        <div class="langkah-item" id="langkah-3">
                            <span class="langkah-nomor">3</span>
                            <div>
                                <div class="langkah-judul">Konfirmasi</div>
                                <button type="submit" id="tombol-checkin" class="tombol tombol-utama tombol-penuh" disabled>Check-in</button>
                            </div>
                        </div>
                    </form>

                    {{-- Mode check-out: slot terisi dipilih --}}
                    <div id="mode-checkout" hidden>
                        <p class="label">Kendaraan di slot <span id="co-slot" class="mono"></span></p>
                        <dl class="rincian">
                            <dt>Plat</dt><dd id="co-plat" class="besar"></dd>
                            <dt>Masuk</dt><dd id="co-masuk"></dd>
                            <dt>Durasi</dt><dd id="co-durasi"></dd>
                            <dt>Biaya saat ini</dt><dd id="co-biaya" class="besar"></dd>
                        </dl>
                        <p class="redup" style="font-size:12px">Biaya akhir dihitung ulang saat tombol ditekan.</p>
                        <div class="kolom-form">
                            <button type="button" id="tombol-checkout" class="tombol tombol-utama tombol-penuh">Check-out</button>
                            <button type="button" id="tombol-batal" class="tombol tombol-penuh">Batal</button>
                        </div>
                    </div>

                    @if ($tarif)
                        <div class="info-tarif">
                            Tarif {{ $jenis->value }}: {{ \App\Services\KalkulatorTarif::MENIT_GRATIS }} mnt pertama gratis · jam pertama {{ \App\Support\Format::rupiah($tarif->tarif_jam_pertama) }}
                            · berikutnya {{ \App\Support\Format::rupiah($tarif->tarif_jam_berikutnya) }}/jam
                            · maks {{ \App\Support\Format::rupiah($tarif->maks_harian) }}/hari
                        </div>
                    @endif
                </div>
            </aside>
        </div>
    @endif
@endsection

@push('skrip')
    <script src="{{ asset('js/parkir.js') }}?v=5" defer></script>
@endpush
