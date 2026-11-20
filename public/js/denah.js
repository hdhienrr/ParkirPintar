// Penggambar denah slot yang dipakai halaman parkir (admin) dan halaman booking (user).
// Tata letak: 1 blok = 3 slot, 1 zona = 3 blok ke samping x 2 ke bawah, pintu masuk di depan kiri.
window.Denah = (function () {
    'use strict';

    function teksAman(teks) {
        const d = document.createElement('div');
        d.textContent = teks;
        return d.innerHTML;
    }

    /**
     * @param {HTMLElement} wadah
     * @param {Array} slot  daftar slot dengan zona, blok, urutan, nomor, kode_slot, status
     * @param {Object} opsi
     *   dipilih: id slot yang dipilih atau null
     *   gambar: URL gambar kendaraan dari atas
     *   gambarSaatTerisi: tampilkan gambar kendaraan di slot terisi (default true)
     *   bisaDipilih(slot): boleh diklik atau tidak (default semua)
     *   keterangan(slot): teks tambahan untuk title dan aria-label
     *   onPilih(id)
     */
    function gambar(wadah, slot, opsi) {
        const perZona = new Map();
        for (const s of slot) {
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
                for (const s of isi.sort((x, y) => x.urutan - y.urutan)) b.appendChild(tombolSlot(s, opsi));
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

        wadah.replaceChildren(lantai);
    }

    function tombolSlot(s, opsi) {
        const t = document.createElement('button');
        t.type = 'button';
        t.dataset.slot = s.id;
        const dipilih = opsi.dipilih === s.id;
        const terisi = s.status === 'terisi';
        t.className = 'slot' + (terisi ? ' terisi' : '') + (dipilih ? ' dipilih' : '');
        t.setAttribute('aria-pressed', dipilih ? 'true' : 'false');

        const keterangan = opsi.keterangan ? opsi.keterangan(s) : '';
        const statusTeks = dipilih ? 'dipilih' : s.status;
        t.setAttribute('aria-label', `Slot ${s.kode_slot}, ${statusTeks}` + (keterangan ? `, ${keterangan}` : ''));
        t.title = `${s.kode_slot} · ${keterangan || s.status}`;

        // Slot kosong yang dipilih menampilkan pratinjau samar kendaraan yang akan parkir di situ.
        const ikon = terisi ? '✕' : (dipilih ? '' : '✓');
        const tampilGambarTerisi = opsi.gambarSaatTerisi !== false;
        const gambarSlot = terisi
            ? (tampilGambarTerisi ? `<img src="${opsi.gambar}" alt="">` : '')
            : (dipilih ? `<img class="pratinjau" src="${opsi.gambar}" alt="">` : '');
        t.innerHTML = `<span class="slot-ikon" aria-hidden="true">${ikon}</span>`
            + gambarSlot
            + `<span class="slot-nomor">${String(s.nomor).padStart(2, '0')}</span>`;

        const bisa = opsi.bisaDipilih ? opsi.bisaDipilih(s) : true;
        t.disabled = !bisa;
        if (bisa) t.addEventListener('click', () => opsi.onPilih(s.id));
        return t;
    }

    function tampilkanPesan(wadah, jenisPesan, html) {
        if (!jenisPesan) {
            wadah.replaceChildren();
            return;
        }
        const kotak = document.createElement('div');
        kotak.className = `pesan pesan-${jenisPesan}`;
        kotak.setAttribute('role', jenisPesan === 'gagal' ? 'alert' : 'status');
        kotak.innerHTML = `<span class="pesan-ikon" aria-hidden="true">${jenisPesan === 'gagal' ? '!' : '✓'}</span><div></div>`;
        kotak.lastChild.innerHTML = html;
        wadah.replaceChildren(kotak);
    }

    return { gambar, teksAman, tampilkanPesan };
})();
