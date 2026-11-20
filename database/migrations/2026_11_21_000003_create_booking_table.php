<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('kode_booking', 12)->unique();
            // Restrict: slot yang punya booking tidak boleh terhapus diam-diam.
            $table->foreignId('slot_id')->constrained('slot_parkir')->restrictOnDelete();
            $table->string('plat', 15);
            $table->string('jenis', 10);
            $table->dateTime('waktu_mulai');
            $table->dateTime('waktu_selesai');
            $table->string('status', 20)->default('dipesan');
            $table->string('alasan')->nullable();
            $table->dateTime('menunggu_sejak')->nullable();
            $table->foreignId('penyebab_booking_id')->nullable()->constrained('booking')->nullOnDelete();
            $table->dateTime('denda_mulai')->nullable();
            $table->dateTime('waktu_keluar')->nullable();
            $table->unsignedInteger('denda')->default(0);
            $table->timestamps();

            // Pengecekan bentrok mencari booking berlaku pada slot dan rentang waktu tertentu.
            $table->index(['slot_id', 'status', 'waktu_mulai']);
            $table->index(['user_id', 'status']);
            $table->index(['plat', 'status']);
        });

        DB::statement("ALTER TABLE booking ADD CONSTRAINT booking_jenis_check CHECK (jenis IN ('mobil', 'motor'))");
        DB::statement("ALTER TABLE booking ADD CONSTRAINT booking_status_check CHECK (status IN ('dipesan', 'menunggu', 'aktif', 'selesai', 'dibatalkan', 'hangus', 'dibatalkan_sistem'))");
        DB::statement('ALTER TABLE booking ADD CONSTRAINT booking_waktu_check CHECK (waktu_selesai > waktu_mulai)');
    }

    public function down(): void
    {
        Schema::dropIfExists('booking');
    }
};
