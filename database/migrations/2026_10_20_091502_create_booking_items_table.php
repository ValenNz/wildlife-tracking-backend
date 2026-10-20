<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_items', function (Blueprint $table) {
            $table->id('item_id');
            $table->unsignedBigInteger('booking_id');
            $table->unsignedInteger('ticket_type_id');
            $table->unsignedSmallInteger('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('subtotal', 12, 2)->storedAs('quantity * unit_price');
            
            $table->foreign('booking_id')->references('booking_id')->on('bookings')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('ticket_type_id')->references('ticket_type_id')->on('ticket_types')->restrictOnDelete()->cascadeOnUpdate();
            $table->unique(['booking_id', 'ticket_type_id']);
            $table->index('ticket_type_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_items');
    }
};