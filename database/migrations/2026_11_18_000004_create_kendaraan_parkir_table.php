<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kendaraan_parkir', function (Blueprint $table) {
            $table->id();
            // Tanpa cascade: MySQL melarang aksi CASCADE/SET NULL pada kolom dasar kolom generated (slot_aktif).
            $table->foreignId('slot_id')->constrained('slot_parkir')->restrictOnDelete();
            $table->string('plat', 15);
            $table->string('jenis', 10);
            $table->dateTime('waktu_masuk');
            $table->dateTime('waktu_keluar')->nullable()->index();
            $table->unsignedInteger('biaya')->nullable();
            $table->unsignedInteger('durasi_menit')->nullable();

            // Terisi hanya selama kendaraan masih di dalam, jadi unique index-nya
            // menolak plat ganda dan slot ganda yang aktif, tapi mengizinkan riwayat.
            $table->string('plat_aktif', 15)->nullable()->storedAs('IF(waktu_keluar IS NULL, plat, NULL)');
            $table->unsignedBigInteger('slot_aktif')->nullable()->storedAs('IF(waktu_keluar IS NULL, slot_id, NULL)');

            $table->timestamps();

            $table->unique('plat_aktif');
            $table->unique('slot_aktif');
            $table->index('plat');
        });

        DB::statement("ALTER TABLE kendaraan_parkir ADD CONSTRAINT kendaraan_parkir_jenis_check CHECK (jenis IN ('mobil', 'motor'))");
        DB::statement('ALTER TABLE kendaraan_parkir ADD CONSTRAINT kendaraan_parkir_waktu_check CHECK (waktu_keluar IS NULL OR waktu_keluar >= waktu_masuk)');
        DB::statement('ALTER TABLE kendaraan_parkir ADD CONSTRAINT kendaraan_parkir_selesai_check CHECK ((waktu_keluar IS NULL) = (biaya IS NULL) AND (waktu_keluar IS NULL) = (durasi_menit IS NULL))');
    }

    public function down(): void
    {
        Schema::dropIfExists('kendaraan_parkir');
    }
};
