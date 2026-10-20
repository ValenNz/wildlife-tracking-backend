<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_log', function (Blueprint $table) {
            $table->unsignedBigInteger('log_id')->primary();
            $table->unsignedInteger('user_id')->nullable();
            $table->string('action', 100);
            $table->timestamp('timestamp')->useCurrent();
            $table->text('details')->nullable();
            
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('set null')->onUpdate('cascade');
            $table->index(['user_id', 'timestamp'], 'idx_log_user_time');
            $table->index('timestamp', 'idx_log_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_log');
    }
};