<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('geozones', function (Blueprint $table) {
            $table->id('zone_id');
            $table->string('zone_name');
            $table->enum('zone_type', [
                'Permukiman',
                'Jalan Raya',
                'Perkebunan',
                'Koridor Ekologis',
            ]);
            $table->json('polygon_data');
            $table->foreignId('created_by')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->timestamps();

            $table->index('zone_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('geozones');
    }
};
