<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aipredictions', function (Blueprint $table) {
            $table->dropColumn(['confidence_score', 'predicted_at']);
            $table->decimal('confidence', 5, 4)->nullable()->after('predicted_species_id');
            $table->string('model_version', 50)->after('confidence');
            $table->enum('processing_status', ['queued', 'processing', 'completed', 'failed'])->default('queued')->after('model_version');
            $table->timestamp('processed_at')->nullable()->after('processing_status');
            $table->enum('verification_status', ['pending', 'confirmed', 'rejected'])->default('pending')->after('processed_at');
            $table->timestamp('verified_at')->nullable()->after('verified_by');
        });
    }

    public function down(): void
    {
        Schema::table('aipredictions', function (Blueprint $table) {
            $table->dropColumn(['confidence', 'model_version', 'processing_status', 'processed_at', 'verification_status', 'verified_at']);
            $table->decimal('confidence_score', 5, 2)->after('predicted_species_id');
            $table->timestamp('predicted_at')->after('confidence_score');
        });
    }
};