<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aggregatereports', function (Blueprint $table) {
            $table->dropColumn(['report_type', 'data_summary']);
            $table->enum('report_type', ['daily', 'weekly', 'monthly'])->after('generated_by');
            $table->string('file_path', 500)->nullable()->after('generated_at');
        });
    }

    public function down(): void
    {
        Schema::table('aggregatereports', function (Blueprint $table) {
            $table->dropColumn(['report_type', 'file_path']);
            $table->string('report_type')->after('generated_by');
            $table->json('data_summary')->after('period_end');
        });
    }
};