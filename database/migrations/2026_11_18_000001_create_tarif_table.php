<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tarif', function (Blueprint $table) {
            $table->id();
            $table->string('jenis', 10)->unique();
            $table->unsignedInteger('tarif_jam_pertama');
            $table->unsignedInteger('tarif_jam_berikutnya');
            $table->unsignedInteger('maks_harian');
            $table->timestamps();
        });

        DB::statement("ALTER TABLE tarif ADD CONSTRAINT tarif_jenis_check CHECK (jenis IN ('mobil', 'motor'))");
        DB::statement('ALTER TABLE tarif ADD CONSTRAINT tarif_maks_harian_check CHECK (maks_harian >= tarif_jam_pertama)');
    }

    public function down(): void
    {
        Schema::dropIfExists('tarif');
    }
};
