<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parking_spots', function (Blueprint $table) {
            $table->id();
            $table->string('spot_code')->unique(); 
            $table->enum('vehicle_type', ['mobil', 'motor']);
            $table->enum('status', ['available', 'pending', 'occupied'])->default('available');
            $table->decimal('hourly_rate', 10, 2); 
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parking_spots');
    }
};
