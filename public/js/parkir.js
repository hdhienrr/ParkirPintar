// Denah slot, check-in, dan check-out. Data selalu dimuat dari /areas/{id}/slots.
(function () {
    'use strict';

    const akar = document.getElementById('halaman-parkir');
    if (!akar) return;

    const jenis = akar.dataset.jenis;
    const gambar = akar.dataset.gambar;
    const dasar = akar.dataset.dasar.replace(/\/$/, '');
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const $ = (id) => document.getElementById(id);

    const el = {
        denah: $('denah'),
        pesan: $('pesan'),
        ringkasan: $('ringkasan-area'),
        formCheckin: $('mode-checkin'),
        modeCheckout: $('mode-checkout'),
        plat: $('plat'),
        infoSlot: $('info-slot'),
        tombolCheckin: $('tombol-checkin'),
        tombolCheckout: $('tombol-checkout'),
        tombolBatal: $('tombol-batal'),
    };

    const rupiah = (n) => (n === null || n === undefined) ? '-' : 'Rp' + Number(n).toLocaleString('id-ID');
    const durasi = (menit) => {
        if (menit === null || menit === undefined) return '-';
        const j = Math.floor(menit / 60);
        const m = menit % 60;
        return j > 0 ? `${j} j ${m} mnt` : `${m} mnt`;
    };
    const waktu = (iso) => new Date(iso).toLocaleString('id-ID', {
        day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit', timeZone: 'Asia/Jakarta',
    });

    const keadaan = {
        areaId: Number(akar.dataset.area),
        area: null,
        slot: [],
        dipilih: null, // id slot
        sibuk: false,
    };

    // ---------- Data ----------

    async function kirim(url, opsi = {}) {
        const res = await fetch(url, {
            ...opsi,
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                ...(opsi.headers || {}),
            },
        });
        const isi = await res.json().catch(() => ({}));
        if (!res.ok) {
            const pesan = isi.errors
                ? Object.values(isi.errors).flat().join(' ')
                : (isi.message || 'Terjadi kesalahan. Coba lagi.');
            throw new Error(pesan);
        }
        return isi;
    }

    async function muatDenah({ diam = false } = {}) {
        if (!diam) el.denah.innerHTML = '<div class="denah-status">Memuat denah…</div>';
        try {
            const { data } = await kirim(`${dasar}/areas/${keadaan.areaId}/slots`, { method: 'GET' });
            keadaan.area = data.area;
            keadaan.slot = data.slot;
            if (keadaan.dipilih && !keadaan.slot.some((s) => s.id === keadaan.dipilih)) keadaan.dipilih = null;
            gambarDenah();
            perbaruiPanel();
            perbaruiRingkasan();
        } catch (e) {
            if (!diam) el.denah.innerHTML = `<div class="denah-status">Denah gagal dimuat: ${teksAman(e.message)}</div>`;
        }
    }

    // ---------- Denah ----------

    function teksAman(teks) {
        const d = document.createElement('div');
        d.textContent = teks;
        return d.innerHTML;
    }

    function gambarDenah() {
        const perZona = new Map();
        for (const s of keadaan.slot) {
            if (!perZona.has(s.zona)) perZona.set(s.zona, new Map());
            const blok = perZona.get(s.zona);
            if (!blok.has(s.blok)) blok.set(s.blok, []);
            blok.get(s.blok).push(s);
        }

        const jumlahZona = perZona.size;
        const kolomZona = Math.min(2, Math.max(1, jumlahZona));
        const barisZona = Math.ceil(jumlahZona / kolomZona);

        const lantai = document.createElement('div');
        lantai.className = 'lantai';

        const jalur = document.createElement('div');
        jalur.className = 'jalur';
        jalur.innerHTML = '<span aria-hidden="true">↑</span><span class="teks-tegak">JALUR KENDARAAN</span><span aria-hidden="true">↑</span>';
        lantai.appendChild(jalur);

        const zonaGrid = document.createElement('div');
        zonaGrid.className = 'zona-grid';
        zonaGrid.style.gridTemplateColumns = `repeat(${kolomZona}, auto)`;

        // Bagian depan ada di bawah denah, jadi zona pertama (dekat pintu) di kiri bawah.
        for (const [zona, blokMap] of [...perZona.entries()].sort((a, b) => a[0] - b[0])) {
            const z = document.createElement('div');
            z.className = 'zona';
            z.style.gridColumn = String((zona % kolomZona) + 1);
            z.style.gridRow = String(barisZona - Math.floor(zona / kolomZona));

            const label = document.createElement('div');
            label.className = 'zona-label';
            label.textContent = `Zona ${zona + 1}`;
            z.appendChild(label);

            const blokGrid = document.createElement('div');
            blokGrid.className = 'blok-grid';
            for (const [blok, isi] of [...blokMap.entries()].sort((a, b) => a[0] - b[0])) {
                const b = document.createElement('div');
                b.className = 'blok';
                b.style.gridColumn = String((blok % 3) + 1);
                b.style.gridRow = String(2 - Math.floor(blok / 3));
                for (const s of isi.sort((x, y) => x.urutan - y.urutan)) b.appendChild(tombolSlot(s));
                blokGrid.appendChild(b);
            }
            z.appendChild(blokGrid);
            zonaGrid.appendChild(z);
        }
        lantai.appendChild(zonaGrid);

        const depan = document.createElement('div');
        depan.className = 'depan';
        depan.innerHTML = '<span class="pintu">▲ PINTU MASUK</span><span>DEPAN</span>';
        lantai.appendChild(depan);

        el.denah.replaceChildren(lantai);
    }

    function tombolSlot(s) {
        const t = document.createElement('button');
        t.type = 'button';
        t.dataset.slot = s.id;
        const dipilih = keadaan.dipilih === s.id;
        t.className = 'slot' + (s.status === 'terisi' ? ' terisi' : '') + (dipilih ? ' dipilih' : '');
        t.setAttribute('aria-pressed', dipilih ? 'true' : 'false');

        const statusTeks = dipilih ? 'dipilih' : s.status;
        t.setAttribute('aria-label', `Slot ${s.kode_slot}, ${statusTeks}` + (s.kendaraan ? `, ${s.kendaraan.plat}` : ''));
        t.title = s.kendaraan ? `${s.kode_slot} · ${s.kendaraan.plat}` : `${s.kode_slot} · kosong`;

        // Slot kosong yang dipilih menampilkan pratinjau samar kendaraan yang akan parkir di situ.
        const terisi = s.status === 'terisi';
        const ikon = terisi ? '✕' : (dipilih ? '' : '✓');
        const gambarSlot = terisi
            ? `<img src="${gambar}" alt="">`
            : (dipilih ? `<img class="pratinjau" src="${gambar}" alt="">` : '');
        t.innerHTML = `<span class="slot-ikon" aria-hidden="true">${ikon}</span>`
            + gambarSlot
            + `<span class="slot-nomor">${String(s.nomor).padStart(2, '0')}</span>`;

        t.addEventListener('click', () => pilihSlot(s.id));
        return t;
    }

    // ---------- Panel ----------

    function slotDipilih() {
        return keadaan.slot.find((s) => s.id === keadaan.dipilih) || null;
    }

    function pilihSlot(id) {
        keadaan.dipilih = keadaan.dipilih === id ? null : id;
        tampilkanPesan(null);
        gambarDenah();
        perbaruiPanel();
        if (slotDipilih()?.status === 'kosong' && !el.plat.value.trim()) el.plat.focus();
    }

    function perbaruiPanel() {
        const s = slotDipilih();
        const terisi = s?.status === 'terisi';

        el.formCheckin.hidden = terisi;
        el.modeCheckout.hidden = !terisi;

        if (terisi) {
            $('co-slot').textContent = s.kode_slot;
            $('co-plat').textContent = s.kendaraan.plat;
            $('co-masuk').textContent = waktu(s.kendaraan.waktu_masuk);
            $('co-durasi').textContent = durasi(s.kendaraan.durasi_menit);
            $('co-biaya').textContent = rupiah(s.kendaraan.perkiraan_biaya);
            return;
        }

        if (s) {
            el.infoSlot.className = 'kotak-info terisi-pilihan';
            el.infoSlot.innerHTML = `<span class="mono">${teksAman(s.kode_slot)}</span> · ${teksAman(keadaan.area.nama)}`;
        } else {
            el.infoSlot.className = 'kotak-info';
            el.infoSlot.textContent = 'Klik slot hijau di denah.';
        }

        const adaPlat = el.plat.value.trim().length > 0;
        $('langkah-1').classList.toggle('selesai', adaPlat);
        $('langkah-2').classList.toggle('selesai', Boolean(s));
        el.tombolCheckin.disabled = !(adaPlat && s) || keadaan.sibuk;
        el.tombolCheckin.textContent = s && adaPlat
            ? `Check-in ${el.plat.value.trim().toUpperCase()} di ${s.kode_slot}`
            : 'Check-in';
    }

    function perbaruiRingkasan() {
        const a = keadaan.area;
        el.ringkasan.textContent = `${a.kosong} kosong · ${a.terisi} terisi`;
        const hitung = document.querySelector(`[data-hitung-area="${a.id}"]`);
        if (hitung) hitung.textContent = `${a.kosong}/${a.total_slot}`;
    }

    function tampilkanPesan(jenisPesan, teks) {
        if (!jenisPesan) {
            el.pesan.replaceChildren();
            return;
        }
        const kotak = document.createElement('div');
        kotak.className = `pesan pesan-${jenisPesan}`;
        kotak.setAttribute('role', jenisPesan === 'gagal' ? 'alert' : 'status');
        kotak.innerHTML = `<span class="pesan-ikon" aria-hidden="true">${jenisPesan === 'gagal' ? '!' : '✓'}</span><div></div>`;
        kotak.lastChild.innerHTML = teks;
        el.pesan.replaceChildren(kotak);
    }

    // ---------- Aksi ----------

    el.plat.addEventListener('input', perbaruiPanel);

    el.formCheckin.addEventListener('submit', async (e) => {
        e.preventDefault();
        const s = slotDipilih();
        if (!s || keadaan.sibuk) return;

        keadaan.sibuk = true;
        perbaruiPanel();
        try {
            const { data } = await kirim(`${dasar}/checkin`, {
                method: 'POST',
                body: JSON.stringify({ slot_id: s.id, plat: el.plat.value, jenis }),
            });
            tampilkanPesan('sukses', `Check-in <b class="mono">${teksAman(data.plat)}</b> di slot <b class="mono">${teksAman(data.kode_slot)}</b> berhasil.`);
            el.plat.value = '';
            keadaan.dipilih = null;
        } catch (err) {
            tampilkanPesan('gagal', teksAman(err.message));
        } finally {
            keadaan.sibuk = false;
            await muatDenah({ diam: true });
        }
    });

    el.tombolCheckout.addEventListener('click', async () => {
        const s = slotDipilih();
        if (!s?.kendaraan || keadaan.sibuk) return;

        keadaan.sibuk = true;
        el.tombolCheckout.disabled = true;
        try {
            const { data } = await kirim(`${dasar}/checkout/${s.kendaraan.id}`, { method: 'POST' });
            tampilkanPesan('sukses',
                `Check-out <b class="mono">${teksAman(data.plat)}</b> dari ${teksAman(data.kode_slot)} berhasil.`
                + `<br>Durasi <b class="mono">${durasi(data.durasi_menit)}</b> · Biaya <b class="mono">${rupiah(data.biaya)}</b>`);
            keadaan.dipilih = null;
        } catch (err) {
            tampilkanPesan('gagal', teksAman(err.message));
        } finally {
            keadaan.sibuk = false;
            el.tombolCheckout.disabled = false;
            await muatDenah({ diam: true });
        }
    });

    el.tombolBatal.addEventListener('click', () => pilihSlot(keadaan.dipilih));

    document.querySelectorAll('[data-tab-area]').forEach((tab) => {
        tab.addEventListener('click', () => {
            const id = Number(tab.dataset.tabArea);
            if (id === keadaan.areaId) return;
            keadaan.areaId = id;
            keadaan.dipilih = null;
            document.querySelectorAll('[data-tab-area]').forEach((t) => t.setAttribute('aria-selected', String(t === tab)));
            history.replaceState(null, '', `?area=${id}`);
            tampilkanPesan(null);
            muatDenah();
        });
    });

    // Segarkan denah berkala supaya tetap sama dengan database.
    setInterval(() => {
        if (!keadaan.sibuk && document.visibilityState === 'visible') muatDenah({ diam: true });
    }, 20000);

    // Plat bisa dibawa dari kartu check-in cepat di beranda (?plat=...).
    const platAwal = new URLSearchParams(location.search).get('plat');
    if (platAwal) el.plat.value = platAwal;

    muatDenah();
})();
