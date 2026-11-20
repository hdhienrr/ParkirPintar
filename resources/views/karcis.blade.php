@extends('layouts.app')

@use('App\Support\Format')
@use('App\Services\KalkulatorTarif')

@php($sudahKeluar = $kendaraan->waktu_keluar !== null)
@php($denda = $kendaraan->booking?->denda ?? 0)

@section('label', 'Parkir')
@section('judul', $sudahKeluar ? 'Karcis dan struk keluar' : 'Karcis parkir')

@section('aksi-judul')
    <div class="aksi-cetak">
        @if ($kendaraan->booking)
            <a href="{{ route('booking.show', $kendaraan->booking) }}" class="tombol">Bukti booking</a>
        @endif
        <button type="button" class="tombol tombol-utama" onclick="window.print()">Cetak</button>
    </div>
@endsection

@section('isi')
    @include('partials.pesan')

    <div class="struk-grup">
        <article class="struk" aria-label="Karcis parkir {{ $kendaraan->nomor_karcis }}">
            <header class="struk-kepala">
                <strong>ParkirPintar</strong>
                <span>Karcis parkir</span>
            </header>

            <div class="struk-kode mono">{{ $kendaraan->nomor_karcis }}</div>
            <div class="struk-status">Simpan karcis ini sampai keluar</div>

            <dl class="struk-rincian">
                <dt>Plat</dt><dd class="mono">{{ $kendaraan->plat }}</dd>
                <dt>Jenis</dt><dd>{{ ucfirst($kendaraan->jenis) }}</dd>
                <dt>Lantai</dt><dd>{{ $kendaraan->slot->area->nama }}</dd>
                <dt>Slot</dt><dd class="mono">{{ $kendaraan->slot->kode_slot }}</dd>
                <dt>Masuk</dt><dd>{{ Format::tanggal($kendaraan->waktu_masuk) }}, {{ Format::jam($kendaraan->waktu_masuk) }}</dd>
                <dt>Kode booking</dt><dd class="mono">{{ $kendaraan->booking?->kode_booking ?? '-' }}</dd>
            </dl>
        </article>

        @if ($sudahKeluar)
            <article class="struk" aria-label="Struk keluar {{ $kendaraan->nomor_karcis }}">
                <header class="struk-kepala">
                    <strong>ParkirPintar</strong>
                    <span>Struk keluar</span>
                </header>

                <dl class="struk-rincian">
                    <dt>Karcis</dt><dd class="mono">{{ $kendaraan->nomor_karcis }}</dd>
                    <dt>Keluar</dt><dd>{{ Format::tanggal($kendaraan->waktu_keluar) }}, {{ Format::jam($kendaraan->waktu_keluar) }}</dd>
                    <dt>Durasi</dt><dd>{{ Format::durasi($kendaraan->durasi_menit) }}</dd>
                </dl>

                @if ($tarif)
                    <p class="struk-kaki">
                        Tarif {{ $kendaraan->jenis }}: {{ KalkulatorTarif::MENIT_GRATIS }} menit pertama gratis,
                        jam pertama {{ Format::rupiah($tarif->tarif_jam_pertama) }}, berikutnya {{ Format::rupiah($tarif->tarif_jam_berikutnya) }} per jam,
                        paling banyak {{ Format::rupiah($tarif->maks_harian) }} per hari. Sisa jam dibulatkan ke atas.
                    </p>
                @endif

                <dl class="struk-rincian struk-total">
                    <dt>Biaya parkir</dt><dd>{{ Format::rupiah($kendaraan->biaya) }}</dd>
                    @if ($denda > 0)
                        <dt>Denda melewati waktu</dt><dd>{{ Format::rupiah($denda) }}</dd>
                    @endif
                    <dt>Total</dt><dd class="besar">{{ Format::rupiah($kendaraan->biaya + $denda) }}</dd>
                </dl>

                <footer class="struk-kaki">Dibayar di tempat saat keluar.</footer>
            </article>
        @endif
    </div>
@endsection
