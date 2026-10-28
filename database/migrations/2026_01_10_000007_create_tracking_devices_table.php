<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracking_devices', function (Blueprint $table) {
            $table->increments('device_id');
            $table->unsignedInteger('animal_id')->nullable()->unique('uq_devices_animal');
            $table->string('model', 100);
            $table->date('installation_date')->nullable();
            $table->unsignedSmallInteger('battery_life_months')->nullable();
            $table->boolean('is_active')->default(true);
            $table->enum('device_condition', ['normal', 'damaged', 'lost'])->default('normal');
            $table->unsignedTinyInteger('battery_level')->nullable();
            $table->unsignedTinyInteger('signal_strength')->nullable();
            $table->dateTime('last_recorded_at')->nullable();
            $table->foreign('animal_id', 'fk_devices_animal')->references('animal_id')->on('animals')->nullOnDelete()->cascadeOnUpdate();
            $table->index(['is_active', 'last_recorded_at'], 'idx_devices_monitor');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracking_devices');
    }
};
