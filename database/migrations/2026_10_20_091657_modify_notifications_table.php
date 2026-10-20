<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn(['title', 'message']);
            $table->enum('notification_type', ['risk_zone', 'device_alert', 'system'])->after('user_id');
            $table->string('message', 500)->after('notification_type');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn(['notification_type', 'message']);
            $table->string('title')->after('device_id');
            $table->text('message')->after('title');
        });
    }
};