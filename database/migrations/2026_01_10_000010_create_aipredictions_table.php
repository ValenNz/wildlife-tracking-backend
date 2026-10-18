<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aipredictions', function (Blueprint $table) {
            $table->id('prediction_id');
            $table->foreignId('media_id')->constrained('aimediauploads', 'media_id')->cascadeOnDelete();
            $table->foreignId('predicted_species_id')->nullable()->constrained('species', 'species_id')->nullOnDelete();
            $table->decimal('confidence_score', 5, 2);
            $table->timestamp('predicted_at');
            $table->foreignId('verified_by')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->foreignId('verified_species_id')->nullable()->constrained('species', 'species_id')->nullOnDelete();

            $table->index('media_id');
            $table->index('predicted_species_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aipredictions');
    }
};
