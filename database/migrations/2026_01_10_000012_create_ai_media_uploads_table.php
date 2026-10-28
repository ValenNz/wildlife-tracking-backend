<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_media_uploads', function (Blueprint $table) {
            $table->bigIncrements('media_id');
            $table->unsignedInteger('uploaded_by')->nullable();
            $table->unsignedInteger('report_id')->nullable();
            $table->enum('media_type', ['image', 'video']);
            $table->string('file_path', 500);
            $table->dateTime('uploaded_at')->useCurrent();
            $table->foreign('uploaded_by', 'fk_media_user')->references('user_id')->on('users')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('report_id', 'fk_media_report')->references('report_id')->on('incident_reports')->nullOnDelete()->cascadeOnUpdate();
            $table->index('uploaded_by', 'idx_media_user');
            $table->index('report_id', 'idx_media_report');
            $table->index('uploaded_at', 'idx_media_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_media_uploads');
    }
};
