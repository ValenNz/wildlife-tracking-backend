<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animal_geo_zones', function (Blueprint $table) {
            $table->bigIncrements('visit_id');
            $table->unsignedInteger('animal_id');
            $table->unsignedInteger('zone_id');
            $table->unsignedBigInteger('entry_reading_id')->nullable();
            $table->dateTime('entered_at');
            $table->dateTime('exited_at')->nullable();
            $table->foreign('animal_id', 'fk_ag_animal')->references('animal_id')->on('animals')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('zone_id', 'fk_ag_zone')->references('zone_id')->on('geo_zones')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('entry_reading_id', 'fk_ag_reading')->references('reading_id')->on('gps_readings')->nullOnDelete()->cascadeOnUpdate();
            $table->index(['animal_id', 'entered_at'], 'idx_ag_animal_time');
            $table->index(['zone_id', 'entered_at'], 'idx_ag_zone_time');
            $table->index('entry_reading_id', 'idx_ag_entry_reading');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animal_geo_zones');
    }
};
