<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('geozones', function (Blueprint $table) {
            $table->dropIndex(['zone_type']);
            $table->dropColumn(['zone_type', 'polygon_data', 'updated_at']);
            $table->string('zone_type', 50)->after('zone_name');
            $table->decimal('center_lat', 9, 6)->nullable()->after('zone_type');
            $table->decimal('center_lon', 10, 6)->nullable()->after('center_lat');
            $table->decimal('radius_km', 8, 3)->nullable()->after('center_lon');
            $table->json('polygon_geojson')->nullable()->after('radius_km');
        });
    }

    public function down(): void
    {
        Schema::table('geozones', function (Blueprint $table) {
            $table->dropColumn(['zone_type', 'center_lat', 'center_lon', 'radius_km', 'polygon_geojson']);
            $table->enum('zone_type', [
                'Permukiman',
                'Jalan Raya',
                'Perkebunan',
                'Koridor Ekologis',
            ])->after('zone_name');
            $table->json('polygon_data')->after('zone_type');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate()->after('created_at');
            $table->index('zone_type');
        });
    }
};