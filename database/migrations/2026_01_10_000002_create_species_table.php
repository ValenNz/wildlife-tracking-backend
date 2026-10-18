<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('species', function (Blueprint $table) {
            $table->id('species_id');
            $table->string('common_name');
            $table->string('scientific_name')->unique();
            $table->enum('conservation_status', [
                'Critically Endangered',
                'Endangered',
                'Vulnerable',
            ]);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('species');
    }
};
