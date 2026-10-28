<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gps_readings', function (Blueprint $table) {
            $table->bigIncrements('reading_id');
            $table->unsignedInteger('device_id');
            $table->decimal('latitude', 9, 6);
            $table->decimal('longitude', 10, 6);
            $table->dateTime('recorded_at');
            $table->decimal('altitude_meters', 8, 2)->nullable();
            $table->decimal('accuracy_meters', 6, 2)->nullable();
            $table->char('checksum', 64)->nullable();
            $table->foreign('device_id', 'fk_gps_device')->references('device_id')->on('tracking_devices')->restrictOnDelete()->cascadeOnUpdate();
            $table->unique(['device_id', 'recorded_at'], 'uq_gps_device_time');
            $table->index('recorded_at', 'idx_gps_recorded_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gps_readings');
    }
};
