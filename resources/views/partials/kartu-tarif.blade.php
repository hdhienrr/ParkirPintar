{{--
    Kartu tarif satu jenis kendaraan, dipakai di beranda dan halaman peraturan.
    Parameter: $tarif (App\Models\Tarif), $catatan (HTML tambahan setelah batas harian, boleh kosong).
--}}
@php($catatan ??= null)
<article class="tarif" data-muncul aria-labelledby="tarif-{{ $tarif->jenis }}">
    <div class="tarif-kepala">
        <h3 id="tarif-{{ $tarif->jenis }}">Parkir {{ $tarif->jenis }}</h3>
        <p>Dihitung per jam, pecahan jam dibulatkan ke atas.</p>
    </div>

    <div class="tarif-alur">
        <div class="tarif-tahap tarif-gratis">
            <span class="tarif-titik" aria-hidden="true"></span>
            <span class="tarif-waktu">{{ \App\Services\KalkulatorTarif::MENIT_GRATIS }} menit pertama</span>
            <strong>Gratis</strong>
        </div>
        <div class="tarif-tahap">
            <span class="tarif-titik" aria-hidden="true"></span>
            <span class="tarif-waktu">Jam pertama</span>
            <strong>{{ \App\Support\Format::rupiah($tarif->tarif_jam_pertama) }}</strong>
        </div>
        <div class="tarif-tahap">
            <span class="tarif-titik" aria-hidden="true"></span>
            <span class="tarif-waktu">Tiap jam berikutnya</span>
            <strong>+{{ \App\Support\Format::rupiah($tarif->tarif_jam_berikutnya) }}</strong>
        </div>
    </div>

    <p class="tarif-catatan">
        Sehari paling banyak <b>{{ \App\Support\Format::rupiah($tarif->maks_harian) }}</b>.
        {!! $catatan !!}
    </p>
</article>
