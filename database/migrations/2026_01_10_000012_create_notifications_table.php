<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->unsignedBigInteger('notif_id')->primary();
            $table->unsignedInteger('user_id');
            $table->enum('notification_type', ['risk_zone', 'device_alert', 'system']);
            $table->string('message', 500);
            $table->unsignedInteger('animal_id')->nullable();
            $table->unsignedInteger('zone_id')->nullable();
            $table->unsignedInteger('device_id')->nullable();
            $table->boolean('is_read')->default(0);
            $table->timestamp('created_at')->useCurrent();
            
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('animal_id')->references('animal_id')->on('animals')->onDelete('set null')->onUpdate('cascade');
            $table->foreign('zone_id')->references('zone_id')->on('geo_zones')->onDelete('set null')->onUpdate('cascade');
            $table->foreign('device_id')->references('device_id')->on('tracking_devices')->onDelete('set null')->onUpdate('cascade');
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