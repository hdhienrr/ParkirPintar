// Halaman booking: memuat denah slot yang bebas untuk jadwal terpilih, lalu user memilih satu slot.
// Plat tidak pernah dikirim lewat URL; hanya ikut di formulir POST saat konfirmasi.
(function () {
    'use strict';

    const akar = document.getElementById('halaman-booking');
    if (!akar) return;

    const $ = (id) => document.getElementById(id);
    const teksAman = Denah.teksAman;
    const jamTutup = Number(akar.dataset.jamTutup);

    const el = {
        form: $('form-booking'),
        plat: $('plat'),
        pilihanJenis: [...document.querySelectorAll('input[name="jenis"]')],
        tanggal: $('tanggal'),
        jam: $('jam-mulai'), // bukan 'jam': id itu dipakai jam digital di navbar
        durasi: $('durasi'),
        slotId: $('slot_id'),
        tab: $('tab-lantai'),
        ringkasan: $('ringkasan-lantai'),
        denah: $('denah'),
        info: $('info-slot'),
        tombol: $('tombol-booking'),
        pesan: $('pesan'),
    };

    const keadaan = {
        area: [],
        areaId: null,
        dipilih: akar.dataset.slotLama ? Number(akar.dataset.slotLama) : null,
        permintaan: 0,
    };

    const jenis = () => el.pilihanJenis.find((r) => r.checked)?.value;
    const gambar = () => akar.dataset[jenis() === 'motor' ? 'gambarMotor' : 'gambarMobil'];
    const areaAktif = () => keadaan.area.find((a) => a.id === keadaan.areaId) || null;
    const slotDipilih = () => {
        for (const a of keadaan.area) {
            const s = a.slot.find((x) => x.id === keadaan.dipilih);
            if (s) return { area: a, slot: s };
        }
        return null;
    };

    // Durasi yang membuat booking lewat jam tutup tidak bisa dipilih.
    function batasiDurasi() {
        const jam = Number(el.jam.value);
        for (const opsi of el.durasi.options) opsi.disabled = jam + Number(opsi.value) > jamTutup;
        if (el.durasi.selectedOptions[0]?.disabled) {
            const terakhir = [...el.durasi.options].reverse().find((o) => !o.disabled);
            if (terakhir) el.durasi.value = terakhir.value;
        }
    }

    function tungguJenis() {
        el.tab.replaceChildren();
        el.ringkasan.textContent = '';
        el.denah.innerHTML = '<div class="denah-status">Pilih jenis kendaraan (Mobil atau Motor) di samping untuk melihat denah.</div>';
        perbaruiPanel();
    }

    async function muatKetersediaan() {
        // Denah baru dimuat setelah jenis kendaraan dipilih.
        if (!jenis()) {
            tungguJenis();
            return;
        }
        batasiDurasi();
        const nomor = ++keadaan.permintaan;
        const query = new URLSearchParams({
            jenis: jenis(),
            tanggal: el.tanggal.value,
            jam: el.jam.value,
            durasi: el.durasi.value,
        });

        el.denah.innerHTML = '<div class="denah-status">Memuat slot yang bebas…</div>';
        try {
            const res = await fetch(`${akar.dataset.urlKetersediaan}?${query}`, { headers: { Accept: 'application/json' } });
            const isi = await res.json().catch(() => ({}));
            if (nomor !== keadaan.permintaan) return; // jawaban lama, jadwal sudah diganti
            if (!res.ok) throw new Error(isi.errors ? Object.values(isi.errors).flat().join(' ') : (isi.message || 'Jadwal tidak valid.'));

            keadaan.area = isi.data;
            const pilihan = slotDipilih();
            if (!pilihan || pilihan.slot.status !== 'kosong') keadaan.dipilih = null;
            keadaan.areaId = slotDipilih()?.area.id ?? keadaan.area.find((a) => a.kosong > 0)?.id ?? keadaan.area[0]?.id ?? null;
            Denah.tampilkanPesan(el.pesan, null);
            gambarTab();
            gambarDenah();
        } catch (err) {
            if (nomor !== keadaan.permintaan) return;
            keadaan.area = [];
            keadaan.dipilih = null;
            el.tab.replaceChildren();
            el.ringkasan.textContent = '';
            el.denah.innerHTML = '<div class="denah-status">Pilih jadwal lain untuk melihat slot.</div>';
            Denah.tampilkanPesan(el.pesan, 'gagal', teksAman(err.message));
        }
        perbaruiPanel();
    }

    function gambarTab() {
        el.tab.replaceChildren(...keadaan.area.map((a) => {
            const t = document.createElement('button');
            t.type = 'button';
            t.setAttribute('role', 'tab');
            t.setAttribute('aria-selected', String(a.id === keadaan.areaId));
            t.innerHTML = `${teksAman(a.nama)}<span class="hitung">${a.kosong}/${a.total_slot}</span>`;
            t.addEventListener('click', () => {
                keadaan.areaId = a.id;
                gambarTab();
                gambarDenah();
            });
            return t;
        }));
    }

    function gambarDenah() {
        const area = areaAktif();
        if (!area) {
            el.denah.innerHTML = '<div class="denah-status">Belum ada lantai untuk jenis kendaraan ini.</div>';
            el.ringkasan.textContent = '';
            return;
        }
        el.ringkasan.textContent = `${area.kosong} bebas · ${area.total_slot - area.kosong} sudah dibooking`;
        Denah.gambar(el.denah, area.slot, {
            dipilih: keadaan.dipilih,
            gambar: gambar(),
            gambarSaatTerisi: false,
            bisaDipilih: (s) => s.status === 'kosong',
            keterangan: (s) => (s.status === 'terisi' ? 'sudah dibooking' : 'bebas'),
            onPilih: (id) => {
                keadaan.dipilih = keadaan.dipilih === id ? null : id;
                gambarDenah();
                perbaruiPanel();
                if (keadaan.dipilih && !el.plat.value.trim()) el.plat.focus();
            },
        });
    }

    function perbaruiPanel() {
        const pilihan = slotDipilih();
        el.slotId.value = pilihan ? pilihan.slot.id : '';

        if (pilihan) {
            el.info.className = 'kotak-info terisi-pilihan';
            el.info.innerHTML = `<span class="mono">${teksAman(pilihan.slot.kode_slot)}</span> · ${teksAman(pilihan.area.nama)}`;
        } else {
            el.info.className = 'kotak-info';
            el.info.textContent = 'Klik slot bebas di denah.';
        }

        const adaPlat = el.plat.value.trim().length > 0;
        el.tombol.disabled = !(adaPlat && pilihan);
        el.tombol.textContent = adaPlat && pilihan
            ? `Booking ${pilihan.slot.kode_slot} untuk ${el.plat.value.trim().toUpperCase()}`
            : 'Booking';
    }

    el.plat.addEventListener('input', perbaruiPanel);
    // Ganti jenis kendaraan: slot pilihan lama (jenis lain) dilepas, denah dimuat ulang.
    for (const pilihan of el.pilihanJenis) {
        pilihan.addEventListener('change', () => {
            keadaan.dipilih = null;
            muatKetersediaan();
        });
    }
    for (const isian of [el.tanggal, el.jam, el.durasi]) {
        isian.addEventListener('change', muatKetersediaan);
    }
    el.form.addEventListener('submit', () => { el.tombol.disabled = true; });

    muatKetersediaan();
})();
