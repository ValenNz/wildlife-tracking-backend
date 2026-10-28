<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incident_reports', function (Blueprint $table) {
            $table->increments('report_id');
            $table->unsignedInteger('user_id')->nullable();
            $table->string('incident_type', 50);
            $table->string('animal_code', 50)->nullable();
            $table->string('location', 255);
            $table->text('description');
            $table->dateTime('timestamp')->useCurrent();
            $table->string('photo_url', 500)->nullable();
            $table->enum('sync_status', ['pending_sync', 'synced', 'failed'])->default('pending_sync');
            $table->foreign('user_id', 'fk_ir_user')->references('user_id')->on('users')->nullOnDelete()->cascadeOnUpdate();
            $table->index('user_id', 'idx_ir_user');
            $table->index('sync_status', 'idx_ir_sync');
            $table->index('timestamp', 'idx_ir_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_reports');
    }
};
