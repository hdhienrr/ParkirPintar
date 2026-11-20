<?php

namespace App\Http\Controllers;

use App\Exceptions\ParkirDitolak;
use App\Models\AreaParkir;
use App\Models\KendaraanParkir;
use App\Models\SlotParkir;
use App\Models\Tarif;
use App\Services\KalkulatorTarif;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AreaController extends Controller
{
    private const ATURAN_JUMLAH_SLOT = ['integer', 'min:'.SlotParkir::SLOT_PER_ZONA, 'max:216', 'multiple_of:'.SlotParkir::SLOT_PER_ZONA];

    public function index(Request $request): JsonResponse|View
    {
        $area = AreaParkir::denganRingkasan()->orderBy('nama')->get()->map->ringkasan();

        if ($request->wantsJson()) {
            return response()->json(['data' => $area]);
        }

        return view('areas.index', [
            'area' => $area,
            'diubah' => $area->firstWhere('id', (int) $request->query('ubah')),
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100', 'unique:area_parkir,nama'],
            'jenis' => ['required', Rule::in(array_keys(SlotParkir::AWALAN))],
            'lantai' => ['required', 'integer', 'min:1', 'max:20'],
            'jumlah_slot' => ['required', ...self::ATURAN_JUMLAH_SLOT],
        ], $this->pesanValidasi());

        $jenis = $data['jenis'];
        $lantai = (int) $data['lantai'];

        $area = DB::transaction(function () use ($data, $jenis, $lantai) {
            if (SlotParkir::where('kode_slot', SlotParkir::buatKode($jenis, $lantai, 1))->lockForUpdate()->exists()) {
                throw ValidationException::withMessages([
                    'lantai' => "Lantai {$lantai} untuk {$jenis} sudah ada.",
                ]);
            }

            $area = AreaParkir::create(['nama' => $data['nama']]);
            $this->tambahSlot($area, $jenis, $lantai, 1, (int) $data['jumlah_slot']);

            return $area;
        });

        if ($request->wantsJson()) {
            return response()->json(['data' => $this->ringkasan($area)], 201);
        }

        return redirect()->route('areas.index')->with('status', "Area {$area->nama} dibuat dengan {$data['jumlah_slot']} slot.");
    }

    public function update(Request $request, AreaParkir $area): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100', Rule::unique('area_parkir', 'nama')->ignore($area->id)],
            'jumlah_slot' => ['sometimes', ...self::ATURAN_JUMLAH_SLOT],
        ], $this->pesanValidasi());

        DB::transaction(function () use ($area, $data) {
            $area->update(['nama' => $data['nama']]);

            if (isset($data['jumlah_slot'])) {
                $this->aturJumlahSlot($area, (int) $data['jumlah_slot']);
            }
        });

        if ($request->wantsJson()) {
            return response()->json(['data' => $this->ringkasan($area)]);
        }

        return redirect()->route('areas.index')->with('status', "Area {$area->nama} diperbarui.");
    }

    public function destroy(Request $request, AreaParkir $area): JsonResponse|RedirectResponse
    {
        if ($area->kendaraan()->exists()) {
            throw new ParkirDitolak("{$area->nama} punya riwayat parkir, jadi tidak bisa dihapus.");
        }

        $area->delete();

        if ($request->wantsJson()) {
            return response()->json(null, 204);
        }

        return redirect()->route('areas.index')->with('status', "Area {$area->nama} dihapus.");
    }

    /**
     * Denah slot satu area beserta status dan kendaraan yang menempati.
     */
    public function slots(Request $request, AreaParkir $area, KalkulatorTarif $kalkulator): JsonResponse|RedirectResponse
    {
        $ringkasan = $this->ringkasan($area);

        if (! $request->wantsJson()) {
            return redirect()->route('parkir', ['jenis' => $ringkasan['jenis'] ?? 'mobil', 'area' => $area->id]);
        }

        $tarif = Tarif::where('jenis', $ringkasan['jenis'])->first();
        $sekarang = now()->startOfSecond();

        $slot = $area->slot()->with('kendaraanAktif')->get()->sortBy('nomor')->values()
            ->map(function (SlotParkir $slot) use ($tarif, $kalkulator, $sekarang) {
                $kendaraan = $slot->kendaraanAktif;

                return [
                    'id' => $slot->id,
                    'kode_slot' => $slot->kode_slot,
                    'nomor' => $slot->nomor,
                    ...$slot->posisi(),
                    'status' => $kendaraan ? 'terisi' : 'kosong',
                    'kendaraan' => $kendaraan ? $this->kendaraanAktif($kendaraan, $tarif, $kalkulator, $sekarang) : null,
                ];
            });

        return response()->json(['data' => ['area' => $ringkasan, 'slot' => $slot]]);
    }

    private function kendaraanAktif(KendaraanParkir $kendaraan, ?Tarif $tarif, KalkulatorTarif $kalkulator, $sekarang): array
    {
        $perkiraan = $tarif
            ? $kalkulator->hitung($tarif, $kendaraan->waktu_masuk, $sekarang)
            : ['durasi_menit' => null, 'biaya' => null];

        return [
            'id' => $kendaraan->id,
            'plat' => $kendaraan->plat,
            'waktu_masuk' => $kendaraan->waktu_masuk->toIso8601String(),
            'durasi_menit' => $perkiraan['durasi_menit'],
            'perkiraan_biaya' => $perkiraan['biaya'],
        ];
    }

    private function ringkasan(AreaParkir $area): array
    {
        return AreaParkir::denganRingkasan()->findOrFail($area->id)->ringkasan();
    }

    private function tambahSlot(AreaParkir $area, string $jenis, int $lantai, int $dari, int $sampai): void
    {
        $sekarang = now();

        $baris = [];
        for ($nomor = $dari; $nomor <= $sampai; $nomor++) {
            $baris[] = [
                'area_id' => $area->id,
                'kode_slot' => SlotParkir::buatKode($jenis, $lantai, $nomor),
                'jenis' => $jenis,
                'created_at' => $sekarang,
                'updated_at' => $sekarang,
            ];
        }

        SlotParkir::insert($baris);
    }

    /**
     * Menambah slot di belakang, atau mengurangi slot bernomor terbesar
     * selama slot itu belum pernah dipakai.
     */
    private function aturJumlahSlot(AreaParkir $area, int $jumlahBaru): void
    {
        $slot = $area->slot()->lockForUpdate()->get()->sortBy('nomor')->values();
        $jumlahLama = $slot->count();

        if ($jumlahBaru > $jumlahLama) {
            $pertama = $slot->first();
            $this->tambahSlot($area, $pertama->jenis, $pertama->lantai, $jumlahLama + 1, $jumlahBaru);

            return;
        }

        $idDihapus = $slot->filter(fn (SlotParkir $s) => $s->nomor > $jumlahBaru)->pluck('id');

        if ($idDihapus->isEmpty()) {
            return;
        }

        if (KendaraanParkir::whereIn('slot_id', $idDihapus)->exists()) {
            throw ValidationException::withMessages([
                'jumlah_slot' => 'Slot yang akan dikurangi punya riwayat parkir, jadi jumlah slot tidak bisa dikurangi.',
            ]);
        }

        SlotParkir::whereIn('id', $idDihapus)->delete();
    }

    private function pesanValidasi(): array
    {
        return [
            'nama.unique' => 'Nama area sudah dipakai.',
            'jumlah_slot.multiple_of' => 'Jumlah slot harus kelipatan '.SlotParkir::SLOT_PER_ZONA.' (satu zona).',
        ];
    }
}
