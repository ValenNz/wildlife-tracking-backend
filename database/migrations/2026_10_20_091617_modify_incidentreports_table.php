<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incidentreports', function (Blueprint $table) {
            $table->dropIndex(['reported_at']);
            $table->dropColumn(['incident_type', 'reported_at', 'media_url']);
            $table->enum('incident_type', ['konflik', 'perambahan', 'observasi', 'device_alert'])->after('user_id');
            $table->string('animal_code', 50)->nullable()->after('incident_type');
            $table->timestamp('timestamp')->useCurrent()->after('description');
            $table->string('photo_url', 500)->nullable()->after('timestamp');
            $table->enum('sync_status', ['pending_sync', 'synced', 'failed'])->default('pending_sync')->after('photo_url');
        });
    }

    public function down(): void
    {
        Schema::table('incidentreports', function (Blueprint $table) {
            $table->dropColumn(['incident_type', 'animal_code', 'timestamp', 'photo_url', 'sync_status']);
            $table->string('incident_type')->after('user_id');
            $table->timestamp('reported_at')->after('description');
            $table->string('media_url')->nullable()->after('reported_at');
            $table->index('reported_at');
        });
    }
};