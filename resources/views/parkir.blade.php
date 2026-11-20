@extends('layouts.app')

@php($namaJenis = $jenis->label())
@use('App\Support\Format')

@section('label', 'Petugas · check-in & check-out')
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

                    {{-- Check-in hanya dengan kode booking. Pemindahan ke slot lain menyusul di langkah 5. --}}
                    <form id="mode-checkin" class="kolom-form" novalidate>
                        <div class="langkah-judul">Check-in dengan kode booking</div>
                        <div class="isian">
                            <label for="kode-booking">Kode booking</label>
                            <input id="kode-booking" name="kode_booking" class="input-plat" list="pilihan-kode" maxlength="20" autocomplete="off" placeholder="BK-XXXXXXXX" required>
                            <datalist id="pilihan-kode">
                                @foreach ($siapCheckin as $b)
                                    <option value="{{ $b->kode_booking }}">{{ Format::jam($b->waktu_mulai) }} · {{ $b->plat }} · {{ $b->slot->kode_slot }}</option>
                                @endforeach
                            </datalist>
                            <span class="petunjuk">Ketik kode dari pengunjung, atau pilih dari booking hari ini di bawah.</span>
                        </div>
                        <button type="submit" id="tombol-checkin" class="tombol tombol-utama tombol-penuh">Check-in</button>

                        <div>
                            <p class="label">Booking hari ini (<span id="jumlah-siap">{{ $siapCheckin->count() }}</span>)</p>
                            <ul id="daftar-booking" class="daftar-checkin">
                                @forelse ($siapCheckin as $b)
                                    <li data-kode="{{ $b->kode_booking }}">
                                        <button type="button" data-kode="{{ $b->kode_booking }}">
                                            <span class="mono">{{ Format::jam($b->waktu_mulai) }}</span>
                                            <span class="mono">{{ $b->plat }}</span>
                                            <span class="mono">{{ $b->slot->kode_slot }}</span>
                                            @if ($b->status === \App\Enums\StatusBooking::Menunggu)
                                                <span class="status-booking status-menunggu">Menunggu</span>
                                            @endif
                                        </button>
                                    </li>
                                @empty
                                    <li class="redup">Belum ada booking {{ $jenis->value }} hari ini yang menunggu check-in.</li>
                                @endforelse
                            </ul>
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
    <script src="{{ asset('js/denah.js') }}?v=1" defer></script>
    <script src="{{ asset('js/parkir.js') }}?v=8" defer></script>
@endpush
