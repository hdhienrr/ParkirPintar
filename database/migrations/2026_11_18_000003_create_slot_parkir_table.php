<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('slot_parkir', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->constrained('area_parkir')->cascadeOnDelete();
            $table->string('kode_slot', 10);
            $table->string('jenis', 10);
            $table->timestamps();

            $table->unique(['area_id', 'kode_slot']);
            $table->index(['area_id', 'jenis']);
        });

        DB::statement("ALTER TABLE slot_parkir ADD CONSTRAINT slot_parkir_jenis_check CHECK (jenis IN ('mobil', 'motor'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('slot_parkir');
    }
};
