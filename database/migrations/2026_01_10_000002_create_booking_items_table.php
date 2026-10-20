<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_items', function (Blueprint $table) {
            $table->unsignedBigInteger('item_id')->primary();
            $table->unsignedBigInteger('booking_id');
            $table->unsignedInteger('ticket_type_id');
            $table->unsignedSmallInteger('quantity');
            $table->decimal('unit_price', 12, 2)->comment('snapshot harga saat dipesan');
            $table->decimal('subtotal', 12, 2)->storedAs('quantity * unit_price');
            
            $table->foreign('booking_id')->references('booking_id')->on('bookings')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('ticket_type_id')->references('ticket_type_id')->on('ticket_types')->onDelete('restrict')->onUpdate('cascade');
            $table->unique(['booking_id', 'ticket_type_id'], 'uq_bi_booking_type');
            $table->index('ticket_type_id', 'idx_bi_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_items');
    }
};