<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animals', function (Blueprint $table) {
            $table->increments('animal_id');
            $table->unsignedInteger('species_id');
            $table->string('name', 100);
            $table->date('birth_date')->nullable();
            $table->enum('gender', ['male', 'female', 'unknown'])->default('unknown');
            $table->boolean('is_active')->default(true);
            $table->foreign('species_id', 'fk_animals_species')->references('species_id')->on('species')->restrictOnDelete()->cascadeOnUpdate();
            $table->index('species_id', 'idx_animals_species');
            $table->index('is_active', 'idx_animals_is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animals');
    }
};
