<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Denda overstay. Pelaku: kendaraan yang terlambat keluar sampai booking berikutnya batal.
     * Korban: kendaraan dari booking yang batal itu, bila tidak keluar dalam batas waktu.
     */
    public function up(): void
    {
        Schema::table('tarif', function (Blueprint $table) {
            $table->unsignedInteger('denda_pelaku_per_jam')->default(0)->after('maks_harian');
            $table->unsignedInteger('denda_pelaku_maks_harian')->default(0)->after('denda_pelaku_per_jam');
            $table->unsignedInteger('denda_korban_per_jam')->default(0)->after('denda_pelaku_maks_harian');
            $table->unsignedInteger('denda_korban_maks_harian')->default(0)->after('denda_korban_per_jam');
        });
    }

    public function down(): void
    {
        Schema::table('tarif', function (Blueprint $table) {
            $table->dropColumn([
                'denda_pelaku_per_jam',
                'denda_pelaku_maks_harian',
                'denda_korban_per_jam',
                'denda_korban_maks_harian',
            ]);
        });
    }
};
