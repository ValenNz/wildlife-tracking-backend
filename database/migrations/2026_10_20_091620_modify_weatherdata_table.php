<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('weatherdata', function (Blueprint $table) {
            $table->dropColumn(['temperature', 'humidity', 'rainfall', 'recorded_at']);
            $table->decimal('latitude', 9, 6)->after('reading_id');
            $table->decimal('longitude', 10, 6)->after('latitude');
            $table->timestamp('observed_at')->after('longitude');
            $table->decimal('temperature_c', 5, 2)->nullable()->after('observed_at');
            $table->decimal('rainfall_mm', 7, 2)->nullable()->after('temperature_c');
            $table->decimal('humidity_percent', 5, 2)->nullable()->after('rainfall_mm');
            $table->decimal('pressure_hpa', 7, 2)->nullable()->after('humidity_percent');
            $table->string('source', 50)->default('WeatherAPI.com')->after('pressure_hpa');
        });
    }

    public function down(): void
    {
        Schema::table('weatherdata', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude', 'observed_at', 'temperature_c', 'rainfall_mm', 'humidity_percent', 'pressure_hpa', 'source']);
            $table->decimal('temperature', 5, 2)->after('reading_id');
            $table->decimal('humidity', 5, 2)->after('temperature');
            $table->decimal('rainfall', 5, 2)->after('humidity');
            $table->timestamp('recorded_at')->after('rainfall');
        });
    }
};