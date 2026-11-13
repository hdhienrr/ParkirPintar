@php
    $tarifMobil = optional($spots->where('vehicle_type', 'mobil')->first())->hourly_rate ?? 5000;
    $tarifMotor = optional($spots->where('vehicle_type', 'motor')->first())->hourly_rate ?? 2000;
    $jumlahTersedia = $spots->where('status', 'available')->count();
    $jumlahPending  = $spots->where('status', 'pending')->count();
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ParkirPintar</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --latar: #B9C6D2;
            --kartu: #CDD8E2;
            --kecil: #E1E8EE;
            --pucat: #A9C0D6;
            --lantai: #4A5D6F;
            --lorong: #6F8396;
            --teks: #14202B;
            --teks-2: #4F5F6E;
            --garis: #9FB0BF;
            --murni: #FFFFFF;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body { background: var(--latar); color: var(--teks); min-height: 100vh; }
        button { font: inherit; cursor: pointer; border: 0; background: none; color: inherit; }

        .halaman { display: none; min-height: 100vh; }
        .halaman.aktif { display: block; }

        .btn-kembali {
            width: 44px; height: 44px; border-radius: 14px; flex-shrink: 0;
            background: var(--kecil); border: 1px solid var(--garis);
            font-size: 24px; line-height: 1; color: var(--teks-2);
            display: flex; align-items: center; justify-content: center;
        }

        .btn-hitam {
            background: var(--teks); color: var(--murni);
            border-radius: 999px; padding: 16px 32px;
            font-size: 15px; font-weight: 700;
            display: inline-flex; align-items: center; justify-content: center; gap: 10px;
        }

        .btn-hitam:disabled { opacity: .35; cursor: not-allowed; }
        .label-kecil { font-size: 11px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; color: var(--teks-2); }

        .bingkai {
            width: 100%; max-width: 1100px; height: 660px;
            background: var(--kartu); border: 1px solid var(--garis); border-radius: 36px;
            display: grid; grid-template-columns: 400px 1fr; overflow: hidden;
        }

        .kolom-kiri { padding: 36px; display: flex; flex-direction: column; overflow-y: auto; }
        .judul-besar { margin-top: 24px; font-size: 34px; line-height: 1.1; font-weight: 800; }
        .judul-besar span { display: block; font-weight: 400; color: var(--teks-2); }
        .kolom-kiri .btn-hitam { margin-top: auto; width: 100%; }
        .kolom-kanan { position: relative; overflow: hidden; }

        .dash { max-width: 1120px; margin: 0 auto; padding: 48px 32px; }
        .dash-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 32px; }
        .dash-header h1 { font-size: 32px; font-weight: 800; }
        .dash-header p { color: var(--teks-2); font-size: 14px; margin-top: 4px; }
        .ringkasan { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 32px; }
        .ringkasan div { background: var(--kecil); border: 1px solid var(--garis); border-radius: 20px; padding: 20px 24px; }
        .ringkasan strong { display: block; font-size: 32px; font-weight: 800; margin-top: 6px; }
        .pesan { background: var(--kecil); border: 1px solid var(--garis); border-radius: 16px; padding: 16px 20px; font-size: 14px; margin-bottom: 24px; }
        .pesan b { display: block; margin-bottom: 2px; }
        .tabel-kotak { background: var(--kecil); border: 1px solid var(--garis); border-radius: 24px; overflow: hidden; }
        .tabel-judul { padding: 20px 24px; border-bottom: 1px solid var(--garis); display: flex; justify-content: space-between; align-items: center; }
        .tabel-judul h3 { font-size: 16px; font-weight: 700; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th { text-align: left; padding: 12px 24px; color: var(--teks-2); font-size: 11px; letter-spacing: .08em; text-transform: uppercase; background: var(--kartu); }
        td { padding: 14px 24px; border-top: 1px solid var(--garis); }
        .mono { font-family: ui-monospace, Menlo, monospace; font-weight: 600; }
        .pil { display: inline-block; padding: 3px 12px; border-radius: 999px; background: var(--pucat); font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .kosong { text-align: center; color: var(--teks-2); padding: 40px; }

        #halaman-1.aktif { display: flex; align-items: center; justify-content: center; padding: 32px; }

        .kartu-kendaraan-wadah { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-top: 10px; }

        .kk {
            background: var(--kecil); border: 2px solid transparent; border-radius: 26px;
            padding: 12px; text-align: center; display: flex; flex-direction: column; gap: 10px;
        }

        .kk.aktif { background: var(--pucat); border-color: var(--teks); }
        .kk .gambar { background: var(--lantai); border-radius: 16px; height: 96px; display: flex; align-items: center; justify-content: center; }
        .kk .gambar img { height: 76px; object-fit: contain; }
        .kk b { font-size: 13px; letter-spacing: .06em; }
        .kk small { display: block; color: var(--teks-2); font-weight: 600; font-size: 12px; margin-top: 2px; }
        .estimasi { margin-top: 24px; margin-bottom: 24px; }
        .estimasi strong { display: block; font-size: 30px; font-weight: 800; margin-top: 4px; }

        .dial-area { height: 100%; }
        .dial-lingkaran { position: absolute; width: 600px; height: 600px; border-radius: 50%; background: var(--kecil); }
        .dial-takik { position: absolute; width: 14px; height: 40px; border-radius: 8px; background: var(--pucat); }

        .dial-item {
            position: absolute; transform: translate(-50%, -50%);
            text-align: center; color: var(--teks-2);
            transition: left .2s, top .2s, opacity .2s;
        }

        .dial-item .angka { display: block; font-size: 24px; font-weight: 500; }
        .dial-item .satuan { display: block; font-size: 11px; font-weight: 600; margin-top: 2px; }
        .dial-item.aktif { color: var(--teks); }
        .dial-item.aktif .angka { font-size: 72px; font-weight: 800; line-height: 1; }
        .dial-item.aktif .satuan { font-size: 13px; margin-top: 6px; }
        .dial-item.sembunyi { opacity: 0; pointer-events: none; }

        #halaman-2.aktif { display: flex; align-items: center; justify-content: center; padding: 32px; }
        #formBooking { width: 100%; max-width: 1100px; }

        .chip { align-self: flex-start; margin-top: 14px; background: var(--kecil); border: 1px solid var(--garis); border-radius: 999px; padding: 6px 14px; font-size: 12px; font-weight: 700; }

        .kotak-plat { margin-top: 24px; background: var(--kecil); border: 1px solid var(--garis); border-radius: 20px; padding: 14px 18px; }

        .kotak-plat input {
            border: 0; outline: 0; background: transparent; width: 100%; margin-top: 4px;
            font-family: ui-monospace, Menlo, monospace; font-size: 19px; font-weight: 700;
            letter-spacing: .1em; text-transform: uppercase; color: var(--teks);
        }

        .kotak-plat input::placeholder { color: #8797A6; }
        .ringkas { margin-top: 18px; margin-bottom: 20px; }

        .ringkas-baris { display: flex; justify-content: space-between; align-items: center; padding: 11px 0; border-top: 1px solid var(--garis); font-size: 15px; font-weight: 700; }
        .ringkas-baris:last-child { border-bottom: 1px solid var(--garis); font-size: 17px; }

        .lantai-area { height: 100%; background: var(--lantai); padding: 32px 28px; overflow: auto; display: flex; }
        .peta { display: none; flex: 1; align-items: stretch; gap: 20px; }
        .peta.aktif { display: flex; }

        .peta-label {
            writing-mode: vertical-rl; transform: rotate(180deg);
            color: var(--murni); font-weight: 800; letter-spacing: .25em; font-size: 13px;
            text-align: center; padding: 0 2px;
        }

        .peta-isi { flex: 1; display: flex; flex-direction: column; justify-content: center; }
        .baris { display: grid; gap: 14px; }

        .lorong {
            margin: 22px 0; height: 64px; background: var(--lorong); border-radius: 12px;
            position: relative; display: flex; align-items: center; justify-content: center;
        }

        .lorong::before {
            content: ''; position: absolute; left: 20px; right: 20px; top: 50%;
            border-top: 2px dashed rgba(255,255,255,.55);
        }

        .lorong span { position: relative; background: var(--lorong); padding: 0 14px; color: var(--murni); font-size: 20px; font-weight: 600; }

        .slot {
            height: 140px; border: 2px dashed rgba(255,255,255,.75); border-radius: 12px;
            display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px;
            color: var(--murni); text-align: center; background: transparent; min-width: 0;
        }

        .slot .kode { font-size: 14px; font-weight: 700; letter-spacing: .08em; }
        .slot.tersedia { cursor: pointer; }
        .slot.tersedia:hover { background: rgba(255,255,255,.14); }
        .slot.terpilih { background: rgba(255,255,255,.3); border-style: solid; border-color: var(--murni); }
        .slot.terisi { border-color: rgba(255,255,255,.4); cursor: not-allowed; }
        .slot.terisi img { height: 84px; max-width: 90%; object-fit: contain; }
        .slot.terisi .kode { font-size: 10px; font-weight: 600; letter-spacing: .05em; }
    </style>
</head>

<body>

<div id="halaman-0" class="halaman aktif">
<div class="dash">

    <div class="dash-header">
        <div>
            <h1>ParkirPintar</h1>
            <p>Sistem reservasi parkir</p>
        </div>
        <button class="btn-hitam" onclick="keHalaman(1)">Booking Parkir <span>→</span></button>
    </div>

    @if(session('success'))
        <div class="pesan"><b>Booking berhasil dibuat</b></div>
    @endif
    @if(session('error'))
        <div class="pesan"><b>Terjadi kesalahan</b></div>
    @endif

    <div class="ringkasan">
        <div><span class="label-kecil">Total Slot</span><strong>{{ $spots->count() }}</strong></div>
        <div><span class="label-kecil">Tersedia</span><strong>{{ $jumlahTersedia }}</strong></div>
        <div><span class="label-kecil">Pending</span><strong>{{ $jumlahPending }}</strong></div>
        <div><span class="label-kecil">Total Transaksi</span><strong>{{ $recentBookings->count() }}</strong></div>
    </div>

    <div class="tabel-kotak">
        <div class="tabel-judul">
            <h3>Riwayat Reservasi</h3>
            <span class="label-kecil">{{ $recentBookings->count() }} data</span>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Kode</th><th>Slot</th><th>Plat</th><th>Tipe</th>
                    <th>Durasi</th><th>Total</th><th>Status</th><th>Waktu</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentBookings as $b)
                <tr>
                    <td class="mono">{{ $b->booking_code }}</td>
                    <td><b>{{ $b->parkingSpot->spot_code ?? '-' }}</b></td>
                    <td class="mono" style="text-transform:uppercase">{{ $b->plate_number }}</td>
                    <td style="text-transform:capitalize">{{ $b->vehicle_type }}</td>
                    <td>{{ $b->duration_hours }} jam</td>
                    <td><b>Rp {{ number_format($b->total_price, 0, ',', '.') }}</b></td>
                    <td><span class="pil">{{ $b->status }}</span></td>
                    <td style="color:var(--teks-2)">{{ $b->created_at->format('d/m/Y H:i') }}</td>
                </tr>
                @empty
                <tr><td colspan="8" class="kosong">Belum ada data booking.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
</div>

<div id="halaman-1" class="halaman">
<div class="bingkai">

    <div class="kolom-kiri">
        <button type="button" class="btn-kembali" onclick="keHalaman(0)">‹</button>

        <h2 class="judul-besar">Berapa Lama <span>Anda Parkir?</span></h2>

        <div class="label-kecil" style="margin-top:28px">Kendaraan</div>
        <div class="kartu-kendaraan-wadah">
            <div class="kk aktif" id="kk-mobil" onclick="pilihKendaraan('mobil')">
                <div class="gambar"><img src="{{ asset('images/car-top.png') }}" alt="Mobil"></div>
                <div><b>MOBIL</b><small>Rp {{ number_format($tarifMobil, 0, ',', '.') }} / jam</small></div>
            </div>
            <div class="kk" id="kk-motor" onclick="pilihKendaraan('motor')">
                <div class="gambar"><img src="{{ asset('images/motor-top.png') }}" alt="Motor"></div>
                <div><b>MOTOR</b><small>Rp {{ number_format($tarifMotor, 0, ',', '.') }} / jam</small></div>
            </div>
        </div>

        <div class="estimasi">
            <div class="label-kecil">Estimasi Biaya</div>
            <strong id="estimasi">Rp 0</strong>
        </div>

        <button type="button" class="btn-hitam" onclick="keHalaman(2)">Pilih Slot <span>→</span></button>
    </div>

    <div class="kolom-kanan">
        <div class="dial-area" id="dialArea">
            <div class="dial-lingkaran" id="dialLingkaran"></div>
            <div class="dial-takik" id="dialTakik"></div>
        </div>
    </div>

</div>
</div>

<div id="halaman-2" class="halaman">
<form action="{{ route('booking.store') }}" method="POST" id="formBooking">
    @csrf
    <input type="hidden" name="parking_spot_id" id="inpSlot">
    <input type="hidden" name="duration_hours" id="inpDurasi" value="3">

    <div class="bingkai">

        <div class="kolom-kiri">
            <button type="button" class="btn-kembali" onclick="keHalaman(1)">‹</button>

            <h2 class="judul-besar">Pilih <span>Slot Parkir</span></h2>
            <div class="chip">{{ $jumlahTersedia }} slot tersedia</div>

            <div class="kotak-plat">
                <span class="label-kecil">Plat Nomor</span>
                <input type="text" name="plate_number" id="inpPlat" placeholder="B 1234 ABC" required autocomplete="off">
            </div>

            <div class="ringkas">
                <div class="ringkas-baris"><span class="label-kecil">Kendaraan</span><span id="rKendaraan">Mobil</span></div>
                <div class="ringkas-baris"><span class="label-kecil">Durasi</span><span id="rDurasi">3 jam</span></div>
                <div class="ringkas-baris"><span class="label-kecil">Slot</span><span id="rSlot" style="color:var(--teks-2)">Belum dipilih</span></div>
                <div class="ringkas-baris"><span class="label-kecil">Total</span><span id="rTotal">Rp 0</span></div>
            </div>

            <button type="submit" id="btnPesan" class="btn-hitam" disabled>Pesan <span>››</span></button>
        </div>

        <div class="kolom-kanan">
            <div class="lantai-area">
                @foreach(['mobil' => 'Area Mobil', 'motor' => 'Area Motor'] as $tipe => $namaArea)
                    @php
                        $daftar  = $spots->where('vehicle_type', $tipe)->values();
                        $separuh = (int) ceil($daftar->count() / 2);
                        $barisan = [$daftar->take($separuh), $daftar->slice($separuh)->values()];
                        $gambar  = $tipe === 'mobil' ? 'images/car-top.png' : 'images/motor-top.png';
                    @endphp
                    <div class="peta {{ $tipe === 'mobil' ? 'aktif' : '' }}" id="peta-{{ $tipe }}">
                        <div class="peta-label">{{ strtoupper($namaArea) }}</div>
                        <div class="peta-isi">
                            @foreach($barisan as $indeks => $baris)
                                <div class="baris" style="grid-template-columns: repeat({{ max($separuh, 1) }}, minmax(0, 1fr))">
                                    @foreach($baris as $s)
                                        @if($s->status === 'available')
                                            <div class="slot tersedia" id="slot-{{ $s->id }}"
                                                 data-id="{{ $s->id }}" data-kode="{{ $s->spot_code }}" data-tarif="{{ $s->hourly_rate }}">
                                                <span class="kode">{{ $s->spot_code }}</span>
                                            </div>
                                        @else
                                            <div class="slot terisi" id="slot-{{ $s->id }}">
                                                <img src="{{ asset($gambar) }}" alt="">
                                                <span class="kode">{{ $s->spot_code }} · Pending</span>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                                @if($indeks === 0)
                                    <div class="lorong"><span>→</span></div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

    </div>
</form>
</div>

<script>
    const TARIF = { mobil: {{ (int) $tarifMobil }}, motor: {{ (int) $tarifMotor }} };
    const MAKS_JAM = 12;

    let kendaraan = 'mobil';
    let durasi    = 3;
    let slotId    = null;
    let slotTarif = 0;

    const rupiah = n => 'Rp ' + new Intl.NumberFormat('id-ID').format(n);
    const $ = id => document.getElementById(id);

    function keHalaman(n) {
        document.querySelectorAll('.halaman').forEach(h => h.classList.remove('aktif'));
        $('halaman-' + n).classList.add('aktif');
        window.scrollTo(0, 0);

        if (n === 1) {
            if (!itemDial.length) buatDial(); else ukurDial();
            hitungEstimasi();
        }

        if (n === 2) {
            $('inpDurasi').value = durasi;
            $('rKendaraan').innerText = kendaraan === 'mobil' ? 'Mobil' : 'Motor';
            $('rDurasi').innerText = durasi + ' jam';
            $('peta-mobil').classList.toggle('aktif', kendaraan === 'mobil');
            $('peta-motor').classList.toggle('aktif', kendaraan === 'motor');
            resetSlot();
        }
    }

    document.addEventListener('keydown', e => {
        if (!$('halaman-1').classList.contains('aktif')) return;
        if (e.key === 'ArrowDown' || e.key === 'ArrowRight') pilihJam(durasi + 1);
        if (e.key === 'ArrowUp' || e.key === 'ArrowLeft') pilihJam(durasi - 1);
    });

    function pilihKendaraan(tipe) {
        kendaraan = tipe;
        $('kk-mobil').classList.toggle('aktif', tipe === 'mobil');
        $('kk-motor').classList.toggle('aktif', tipe === 'motor');
        hitungEstimasi();
    }

    function hitungEstimasi() {
        $('estimasi').innerText = rupiah(TARIF[kendaraan] * durasi);
    }

    const DIAL = { cx: 0, cy: 0, r: 210, langkah: 0.44, jari: 300 };
    const itemDial = [];

    function ukurDial() {
        const area = $('dialArea');
        DIAL.cx = area.clientWidth;
        DIAL.cy = area.clientHeight / 2;

        const lingkaran = $('dialLingkaran');
        lingkaran.style.left = (DIAL.cx - DIAL.jari) + 'px';
        lingkaran.style.top  = (DIAL.cy - DIAL.jari) + 'px';

        const takik = $('dialTakik');
        takik.style.left = (DIAL.cx - DIAL.jari - 3) + 'px';
        takik.style.top  = (DIAL.cy - 20) + 'px';

        gambarDial();
    }

    function buatDial() {
        const area = $('dialArea');
        for (let jam = 1; jam <= MAKS_JAM; jam++) {
            const el = document.createElement('button');
            el.type = 'button';
            el.className = 'dial-item';
            el.innerHTML = '<span class="angka">' + jam + '</span><span class="satuan">Jam</span>';
            el.onclick = () => pilihJam(jam);
            area.appendChild(el);
            itemDial.push(el);
        }
        ukurDial();
    }

    function gambarDial() {
        itemDial.forEach((el, i) => {
            const jam = i + 1;
            const sudut = (jam - durasi) * DIAL.langkah;
            el.style.left = (DIAL.cx - DIAL.r * Math.cos(sudut)) + 'px';
            el.style.top  = (DIAL.cy + DIAL.r * Math.sin(sudut)) + 'px';
            el.classList.toggle('aktif', jam === durasi);
            el.classList.toggle('sembunyi', Math.abs(jam - durasi) > 2);
        });
    }

    function pilihJam(jam) {
        durasi = Math.min(MAKS_JAM, Math.max(1, jam));
        gambarDial();
        hitungEstimasi();
    }

    $('dialArea').addEventListener('wheel', e => {
        e.preventDefault();
        pilihJam(durasi + (e.deltaY > 0 ? 1 : -1));
    }, { passive: false });

    window.addEventListener('resize', () => { if ($('halaman-1').classList.contains('aktif') && itemDial.length) ukurDial(); });

    document.querySelectorAll('.slot.tersedia').forEach(el => {
        el.addEventListener('click', () => {
            slotId = el.dataset.id;
            slotTarif = parseInt(el.dataset.tarif, 10);
            document.querySelectorAll('.slot').forEach(s => s.classList.remove('terpilih'));
            el.classList.add('terpilih');
            $('inpSlot').value = slotId;
            $('rSlot').innerText = el.dataset.kode;
            $('rSlot').style.color = '';
            hitungTotal();
            cekForm();
        });
    });

    function resetSlot() {
        slotId = null; slotTarif = 0;
        $('inpSlot').value = '';
        $('rSlot').innerText = 'Belum dipilih';
        $('rSlot').style.color = 'var(--teks-2)';
        document.querySelectorAll('.slot').forEach(s => s.classList.remove('terpilih'));
        hitungTotal();
        cekForm();
    }

    function hitungTotal() {
        $('rTotal').innerText = rupiah(slotTarif * durasi);
    }

    function cekForm() {
        $('btnPesan').disabled = !($('inpPlat').value.trim().length > 0 && slotId);
    }

    $('inpPlat').addEventListener('input', e => {
        e.target.value = e.target.value.toUpperCase();
        cekForm();
    });

    hitungEstimasi();
</script>

</body>
</html>