<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidentreports', function (Blueprint $table) {
            $table->id('report_id');
            $table->foreignId('user_id')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->string('location');
            $table->string('incident_type');
            $table->text('description');
            $table->timestamp('reported_at');
            $table->string('media_url')->nullable();

            $table->index('user_id');
            $table->index('reported_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidentreports');
    }
};
