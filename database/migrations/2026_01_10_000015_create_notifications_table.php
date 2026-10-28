<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->bigIncrements('notif_id');
            $table->unsignedInteger('user_id');
            $table->enum('notification_type', ['risk_zone', 'device_alert', 'system']);
            $table->string('message', 500);
            $table->unsignedInteger('animal_id')->nullable();
            $table->unsignedInteger('zone_id')->nullable();
            $table->unsignedInteger('device_id')->nullable();
            $table->boolean('is_read')->default(false);
            $table->dateTime('created_at')->useCurrent();
            $table->foreign('user_id', 'fk_notif_user')->references('user_id')->on('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('animal_id', 'fk_notif_animal')->references('animal_id')->on('animals')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('zone_id', 'fk_notif_zone')->references('zone_id')->on('geo_zones')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('device_id', 'fk_notif_device')->references('device_id')->on('tracking_devices')->nullOnDelete()->cascadeOnUpdate();
            $table->index(['user_id', 'is_read', 'created_at'], 'idx_notif_user_unread');
            $table->index('animal_id', 'idx_notif_animal');
            $table->index('zone_id', 'idx_notif_zone');
            $table->index('device_id', 'idx_notif_device');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
