<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animal_geo_zones', function (Blueprint $table) {
            $table->unsignedBigInteger('visit_id')->primary();
            $table->unsignedInteger('animal_id');
            $table->unsignedInteger('zone_id');
            $table->unsignedBigInteger('entry_reading_id')->nullable();
            $table->timestamp('entered_at');
            $table->timestamp('exited_at')->nullable();
            
            $table->foreign('animal_id')->references('animal_id')->on('animals')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('zone_id')->references('zone_id')->on('geo_zones')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('entry_reading_id')->references('reading_id')->on('gps_readings')->onDelete('set null')->onUpdate('cascade');
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