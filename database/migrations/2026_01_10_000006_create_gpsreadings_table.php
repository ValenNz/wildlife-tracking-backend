<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gpsreadings', function (Blueprint $table) {
            $table->id('reading_id');
            $table->foreignId('device_id')->constrained('trackingdevices', 'device_id')->cascadeOnDelete();
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->timestamp('recorded_at');
            $table->decimal('accuracy_meters', 5, 2)->nullable();

            $table->index('device_id');
            $table->index('recorded_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gpsreadings');
    }
};
