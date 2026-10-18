<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animalgeozones', function (Blueprint $table) {
            $table->id('visit_id');
            $table->foreignId('animal_id')->constrained('animals', 'animal_id')->cascadeOnDelete();
            $table->foreignId('zone_id')->constrained('geozones', 'zone_id')->cascadeOnDelete();
            $table->foreignId('entry_reading_id')->constrained('gpsreadings', 'reading_id')->cascadeOnDelete();
            $table->foreignId('exit_reading_id')->nullable()->constrained('gpsreadings', 'reading_id')->nullOnDelete();
            $table->decimal('duration_hours', 8, 2)->nullable();

            $table->index(['animal_id', 'zone_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animalgeozones');
    }
};
