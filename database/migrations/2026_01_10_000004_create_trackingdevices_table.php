<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trackingdevices', function (Blueprint $table) {
            $table->id('device_id');
            $table->foreignId('animal_id')->unique()->nullable()->constrained('animals', 'animal_id')->nullOnDelete();
            $table->string('model');
            $table->date('installation_date');
            $table->integer('battery_life_months');
            $table->enum('status', ['Active', 'Inactive', 'Maintenance'])->default('Active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trackingdevices');
    }
};
