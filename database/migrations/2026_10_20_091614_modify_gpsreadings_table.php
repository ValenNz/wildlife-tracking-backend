<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gpsreadings', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude', 'accuracy_meters']);
            $table->decimal('latitude', 9, 6)->after('device_id');
            $table->decimal('longitude', 10, 6)->after('latitude');
            $table->decimal('altitude_meters', 8, 2)->nullable()->after('recorded_at');
            $table->decimal('accuracy_meters', 6, 2)->nullable()->after('altitude_meters');
            $table->char('checksum', 64)->nullable()->after('accuracy_meters');
        });
    }

    public function down(): void
    {
        Schema::table('gpsreadings', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude', 'altitude_meters', 'accuracy_meters', 'checksum']);
            $table->decimal('latitude', 10, 8)->after('device_id');
            $table->decimal('longitude', 11, 8)->after('latitude');
            $table->decimal('accuracy_meters', 5, 2)->nullable()->after('recorded_at');
        });
    }
};