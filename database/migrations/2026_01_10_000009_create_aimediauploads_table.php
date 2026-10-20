<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_media_uploads', function (Blueprint $table) {
            $table->unsignedBigInteger('media_id')->primary();
            $table->unsignedInteger('uploaded_by')->nullable();
            $table->unsignedInteger('report_id')->nullable()->comment('opsional: laporan kejadian terkait');
            $table->enum('media_type', ['image', 'video']);
            $table->string('file_path', 500);
            $table->timestamp('uploaded_at')->useCurrent();
            
            $table->foreign('uploaded_by')->references('user_id')->on('users')->onDelete('set null')->onUpdate('cascade');
            $table->foreign('report_id')->references('report_id')->on('incident_reports')->onDelete('set null')->onUpdate('cascade');
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