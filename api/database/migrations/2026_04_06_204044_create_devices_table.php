<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Caregiver
            $table->string('device_token')->unique(); // Untuk autentikasi ESP32 simple
            $table->string('label')->default('Device Lansia');
            $table->float('fall_threshold')->default(2.0);
            $table->integer('orientation_threshold')->default(45);
            $table->integer('immobility_duration')->default(30); // dalam detik
            $table->boolean('is_online')->default(false);
            $table->integer('battery_level')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};