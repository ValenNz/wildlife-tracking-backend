<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('geo_zones', function (Blueprint $table) {
            $table->increments('zone_id');
            $table->string('zone_name', 150);
            $table->string('zone_type', 50);
            $table->decimal('center_lat', 9, 6)->nullable();
            $table->decimal('center_lon', 10, 6)->nullable();
            $table->decimal('radius_km', 8, 3)->nullable();
            $table->json('polygon_geojson')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->foreign('created_by', 'fk_zones_user')->references('user_id')->on('users')->nullOnDelete()->cascadeOnUpdate();
            $table->index('zone_type', 'idx_zones_type');
            $table->index('created_by', 'idx_zones_created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('geo_zones');
    }
};
