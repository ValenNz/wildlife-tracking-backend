<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animals', function (Blueprint $table) {
            $table->id('animal_id');
            $table->foreignId('species_id')->constrained('species', 'species_id')->cascadeOnDelete();
            $table->string('animal_code')->unique();
            $table->date('birth_date')->nullable();
            $table->enum('gender', ['Jantan', 'Betina', 'Tidak Diketahui'])->default('Tidak Diketahui');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('species_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animals');
    }
};
