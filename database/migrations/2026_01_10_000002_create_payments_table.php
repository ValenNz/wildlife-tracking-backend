<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->unsignedBigInteger('payment_id')->primary();
            $table->unsignedBigInteger('booking_id');
            $table->enum('payment_method', ['transfer_bank', 'qris', 'e_wallet', 'kartu_kredit', 'tunai']);
            $table->decimal('amount', 12, 2);
            $table->enum('payment_status', ['pending', 'success', 'failed', 'expired', 'refunded'])->default('pending');
            $table->string('transaction_ref', 100)->nullable()->comment('ID transaksi dari bank/gateway');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->boolean('is_success')->virtualAs("if(payment_status = 'success', 1, NULL)")->comment('penanda pembayaran sukses');
            
            $table->foreign('booking_id')->references('booking_id')->on('bookings')->onDelete('restrict')->onUpdate('cascade');
            $table->unique(['payment_method', 'transaction_ref'], 'uq_pay_method_ref');
            $table->unique(['booking_id', 'is_success'], 'uq_pay_one_success');
            $table->index(['payment_status', 'created_at'], 'idx_pay_status_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};