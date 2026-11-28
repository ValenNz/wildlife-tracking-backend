<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animal_embeddings', function (Blueprint $table) {
            $table->id('embedding_id');
            $table->foreignId('animal_id')->nullable()->constrained('animals', 'animal_id')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('species_id')->nullable()->constrained('species', 'species_id')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('media_id')->nullable()->constrained('ai_media_uploads', 'media_id')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('prediction_id')->nullable()->constrained('ai_predictions', 'prediction_id')->cascadeOnUpdate()->cascadeOnDelete();
            $table->enum('source', ['reference', 'verified', 'detection'])->default('detection');
            $table->string('model', 100);
            $table->json('vector');
            $table->json('bbox')->nullable()->comment('[x, y, lebar, tinggi] relatif 0-1');
            $table->string('file_path', 500)->nullable()->comment('foto referensi di storage privat');
            $table->foreignId('created_by')->nullable()->constrained('users', 'user_id')->cascadeOnUpdate()->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['animal_id', 'source']);
            $table->index('prediction_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animal_embeddings');
    }
};
