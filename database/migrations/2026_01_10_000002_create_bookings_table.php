<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->unsignedBigInteger('booking_id')->primary();
            $table->string('booking_code', 20)->unique()->comment('kode unik, mis. WLT-20261105-A7K2Q9');
            $table->unsignedInteger('visitor_id');
            $table->date('visit_date');
            $table->decimal('total_amount', 12, 2)->default(0.00)->comment('jumlah subtotal booking_items (IDR)');
            $table->enum('booking_status', ['pending', 'paid', 'cancelled', 'expired', 'completed'])->default('pending');
            $table->timestamp('expires_at')->nullable()->comment('batas waktu pembayaran');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            
            $table->foreign('visitor_id')->references('visitor_id')->on('visitors')->onDelete('restrict')->onUpdate('cascade');
            $table->index(['visitor_id', 'created_at'], 'idx_bookings_visitor_time');
            $table->index(['visit_date', 'booking_status'], 'idx_bookings_visit');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};