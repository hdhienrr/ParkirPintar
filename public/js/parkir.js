// Halaman petugas: denah slot, check-in dengan kode booking, dan check-out. Denah dimuat dari /areas/{id}/slots.
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
        kodeBooking: $('kode-booking'),
        daftarBooking: $('daftar-booking'),
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
            // Slot kosong tidak dipilih di sini: check-in selalu lewat kode booking.
            bisaDipilih: (s) => s.status === 'terisi',
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

        el.tombolCheckin.disabled = keadaan.sibuk;
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

    const tautanKarcis = (data) => (data.url_karcis
        ? ` <a href="${teksAman(data.url_karcis)}">Lihat karcis ${teksAman(data.nomor_karcis)}</a>`
        : '');

    // Booking yang sudah check-in tidak perlu muncul lagi di pilihan formulir.
    function hapusDariDaftar(kode) {
        const pilih = `[data-kode="${CSS.escape(kode)}"], option[value="${CSS.escape(kode)}"]`;
        document.querySelectorAll(pilih).forEach((elemen) => elemen.remove());
        $('jumlah-siap').textContent = String(el.daftarBooking.querySelectorAll('li[data-kode]').length);
    }

    el.formCheckin.addEventListener('submit', async (e) => {
        e.preventDefault();
        const kode = el.kodeBooking.value.trim();
        if (!kode || keadaan.sibuk) return;

        keadaan.sibuk = true;
        perbaruiPanel();
        try {
            const isi = await kirim(`${dasar}/checkin`, {
                method: 'POST',
                body: JSON.stringify({ kode_booking: kode }),
            });
            if (isi.data.status === 'menunggu') {
                tampilkanPesan('sukses', teksAman(isi.message));
            } else {
                tampilkanPesan('sukses', `Check-in <b class="mono">${teksAman(isi.data.plat)}</b> di slot <b class="mono">${teksAman(isi.data.kode_slot)}</b> berhasil.` + tautanKarcis(isi.data));
                hapusDariDaftar(isi.data.kode_booking);
            }
            el.kodeBooking.value = '';
        } catch (err) {
            tampilkanPesan('gagal', teksAman(err.message));
        } finally {
            keadaan.sibuk = false;
            perbaruiPanel();
            await muatDenah({ diam: true });
        }
    });

    el.daftarBooking.addEventListener('click', (e) => {
        const tombol = e.target.closest('[data-kode]');
        if (!tombol) return;
        el.kodeBooking.value = tombol.dataset.kode;
        el.kodeBooking.focus();
    });

    el.tombolCheckout.addEventListener('click', async () => {
        const s = slotDipilih();
        if (!s?.kendaraan || keadaan.sibuk) return;

        keadaan.sibuk = true;
        el.tombolCheckout.disabled = true;
        try {
            const isi = await kirim(`${dasar}/checkout/${s.kendaraan.id}`, { method: 'POST' });
            const { data } = isi;
            const menyusul = isi.masuk_menyusul;
            tampilkanPesan('sukses',
                `Check-out <b class="mono">${teksAman(data.plat)}</b> dari ${teksAman(data.kode_slot)} berhasil.`
                + `<br>Durasi <b class="mono">${durasi(data.durasi_menit)}</b> · Biaya <b class="mono">${rupiah(data.biaya)}</b>`
                + (data.denda > 0 ? ` · Denda <b class="mono">${rupiah(data.denda)}</b> · Total <b class="mono">${rupiah(data.total)}</b>` : '')
                + tautanKarcis(data)
                + (menyusul ? `<br>Booking yang menunggu langsung check-in: <b class="mono">${teksAman(menyusul.plat)}</b>.${tautanKarcis(menyusul)}` : ''));
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
