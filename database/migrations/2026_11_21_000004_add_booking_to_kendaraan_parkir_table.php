<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kendaraan dari v1 tidak punya booking dan karcis, jadi kedua kolom boleh kosong.
     */
    public function up(): void
    {
        Schema::table('kendaraan_parkir', function (Blueprint $table) {
            $table->foreignId('booking_id')->nullable()->after('slot_id')->constrained('booking')->restrictOnDelete();
            $table->string('nomor_karcis', 20)->nullable()->unique()->after('booking_id');
        });
    }

    public function down(): void
    {
        Schema::table('kendaraan_parkir', function (Blueprint $table) {
            $table->dropConstrainedForeignId('booking_id');
            $table->dropUnique(['nomor_karcis']);
            $table->dropColumn('nomor_karcis');
        });
    }
};
