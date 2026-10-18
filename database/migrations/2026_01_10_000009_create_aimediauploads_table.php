<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aimediauploads', function (Blueprint $table) {
            $table->id('media_id');
            $table->foreignId('uploaded_by')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->foreignId('report_id')->nullable()->constrained('incidentreports', 'report_id')->nullOnDelete();
            $table->enum('media_type', ['image', 'video']);
            $table->string('file_path');
            $table->timestamp('uploaded_at');

            $table->index('uploaded_by');
            $table->index('report_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aimediauploads');
    }
};
