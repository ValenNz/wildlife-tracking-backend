<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_predictions', function (Blueprint $table) {
            $table->unsignedBigInteger('prediction_id')->primary();
            $table->unsignedBigInteger('media_id');
            $table->unsignedInteger('predicted_species_id')->nullable();
            $table->decimal('confidence', 5, 4)->nullable()->comment('0.0000 - 1.0000');
            $table->string('model_version', 50);
            $table->enum('processing_status', ['queued', 'processing', 'completed', 'failed'])->default('queued');
            $table->timestamp('processed_at')->nullable();
            $table->enum('verification_status', ['pending', 'confirmed', 'rejected'])->default('pending');
            $table->unsignedInteger('verified_species_id')->nullable()->comment('spesies benar menurut petugas');
            $table->unsignedInteger('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            
            $table->foreign('media_id')->references('media_id')->on('ai_media_uploads')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('predicted_species_id')->references('species_id')->on('species')->onDelete('set null')->onUpdate('cascade');
            $table->foreign('verified_by')->references('user_id')->on('users')->onDelete('set null')->onUpdate('cascade');
            $table->foreign('verified_species_id')->references('species_id')->on('species')->onDelete('set null')->onUpdate('cascade');
            $table->unique(['media_id', 'model_version'], 'uq_pred_media_model');
            $table->index('predicted_species_id', 'idx_pred_species');
            $table->index('verification_status', 'idx_pred_verif');
            $table->index('verified_species_id', 'idx_pred_verified_species');
            $table->index('verified_by', 'idx_pred_verified_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_predictions');
    }
};