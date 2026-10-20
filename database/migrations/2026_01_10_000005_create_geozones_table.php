<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('geo_zones', function (Blueprint $table) {
            $table->unsignedInteger('zone_id')->primary();
            $table->string('zone_name', 150);
            $table->string('zone_type', 50)->comment('permukiman, perkebunan, jalan_raya, koridor_ekologis');
            $table->decimal('center_lat', 9, 6)->nullable();
            $table->decimal('center_lon', 10, 6)->nullable();
            $table->decimal('radius_km', 8, 3)->nullable();
            $table->json('polygon_geojson')->nullable()->comment('GeoJSON Polygon WGS84');
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            
            $table->foreign('created_by')->references('user_id')->on('users')->onDelete('set null')->onUpdate('cascade');
            $table->index('zone_type', 'idx_zones_type');
            $table->index('created_by', 'idx_zones_created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('geo_zones');
    }
};