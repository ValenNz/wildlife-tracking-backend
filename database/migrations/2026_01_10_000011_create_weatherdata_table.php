<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weatherdata', function (Blueprint $table) {
            $table->id('weather_id');
            $table->foreignId('reading_id')->constrained('gpsreadings', 'reading_id')->cascadeOnDelete();
            $table->decimal('temperature', 5, 2);
            $table->decimal('humidity', 5, 2);
            $table->decimal('rainfall', 5, 2);
            $table->timestamp('recorded_at');

            $table->index('reading_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weatherdata');
    }
};
