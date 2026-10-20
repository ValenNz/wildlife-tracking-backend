<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id('booking_id');
            $table->string('booking_code', 20)->unique();
            $table->unsignedBigInteger('visitor_id'); 
            $table->date('visit_date');
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->enum('booking_status', ['pending', 'paid', 'cancelled', 'expired', 'completed'])->default('pending');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            
            $table->foreign('visitor_id')->references('visitor_id')->on('visitors')->restrictOnDelete()->cascadeOnUpdate();
            $table->index(['visitor_id', 'created_at']);
            $table->index(['visit_date', 'booking_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};