<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trackingdevices', function (Blueprint $table) {
            $table->dropColumn(['status', 'created_at', 'updated_at']);
            $table->boolean('is_active')->default(true)->after('battery_life_months');
            $table->enum('device_condition', ['normal', 'damaged', 'lost'])->default('normal')->after('is_active');
            $table->unsignedTinyInteger('battery_level')->nullable()->after('device_condition');
            $table->unsignedTinyInteger('signal_strength')->nullable()->after('battery_level');
            $table->timestamp('last_recorded_at')->nullable()->after('signal_strength');
        });
    }

    public function down(): void
    {
        Schema::table('trackingdevices', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'device_condition', 'battery_level', 'signal_strength', 'last_recorded_at']);
            $table->enum('status', ['Active', 'Inactive', 'Maintenance'])->default('Active')->after('battery_life_months');
            $table->timestamps();
        });
    }
};