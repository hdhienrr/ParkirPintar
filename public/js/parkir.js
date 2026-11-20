// Denah slot, check-in, dan check-out. Data selalu dimuat dari /areas/{id}/slots.
(function () {
    'use strict';

    const akar = document.getElementById('halaman-parkir');
    if (!akar) return;

    const jenis = akar.dataset.jenis;
    const gambar = akar.dataset.gambar;
    const dasar = akar.dataset.dasar.replace(/\/$/, '');
    const jedaSegarMs = Number(akar.dataset.segarDetik) * 1000;
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const $ = (id) => document.getElementById(id);
    const teksAman = Denah.teksAman;

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

    function gambarDenah() {
        Denah.gambar(el.denah, keadaan.slot, {
            dipilih: keadaan.dipilih,
            gambar,
            keterangan: (s) => (s.kendaraan ? s.kendaraan.plat : ''),
            onPilih: pilihSlot,
        });
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

    function tampilkanPesan(jenisPesan, html) {
        Denah.tampilkanPesan(el.pesan, jenisPesan, html);
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
    }, jedaSegarMs);

    muatDenah();
})();
