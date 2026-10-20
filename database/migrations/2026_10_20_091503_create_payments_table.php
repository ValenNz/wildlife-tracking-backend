<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id('payment_id');
            $table->unsignedBigInteger('booking_id');
            $table->enum('payment_method', ['transfer_bank', 'qris', 'e_wallet', 'kartu_kredit', 'tunai']);
            $table->decimal('amount', 12, 2);
            $table->enum('payment_status', ['pending', 'success', 'failed', 'expired', 'refunded'])->default('pending');
            $table->string('transaction_ref', 100)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            
            $table->foreign('booking_id')->references('booking_id')->on('bookings')->restrictOnDelete()->cascadeOnUpdate();
            $table->unique(['payment_method', 'transaction_ref']);
            // Note: is_success virtual column is MySQL-specific, not portable
            $table->index(['payment_status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};