<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aggregatereports', function (Blueprint $table) {
            $table->id('report_id');
            $table->foreignId('generated_by')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->string('report_type');
            $table->date('period_start');
            $table->date('period_end');
            $table->json('data_summary');
            $table->timestamp('generated_at');

            $table->index('generated_by');
            $table->index(['period_start', 'period_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aggregatereports');
    }
};
