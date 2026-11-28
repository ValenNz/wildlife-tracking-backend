<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_sessions', function (Blueprint $table) {
            $table->id('session_id');
            $table->foreignId('cashier_id')->constrained('users', 'user_id')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('counter_name', 100)->comment('nama loket, mis. Loket Bukit Lawang');
            $table->timestamp('opened_at');
            $table->unsignedInteger('opening_cash')->default(0)->comment('modal kembalian awal');
            $table->timestamp('closed_at')->nullable();
            $table->unsignedInteger('expected_cash')->nullable()->comment('dihitung sistem saat tutup');
            $table->unsignedInteger('counted_cash')->nullable()->comment('dihitung kasir saat tutup');
            $table->integer('cash_difference')->nullable()->comment('counted - expected');
            $table->string('closing_note', 500)->nullable();
            $table->timestamps();

            $table->index(['cashier_id', 'closed_at']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->enum('channel', ['online', 'pos'])->default('online')->after('user_id');
            $table->foreignId('pos_session_id')->nullable()->after('channel')
                ->constrained('pos_sessions', 'session_id')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('sold_by')->nullable()->after('pos_session_id')
                ->constrained('users', 'user_id')->cascadeOnUpdate()->nullOnDelete();

            $table->index(['channel', 'visit_date']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->enum('method', ['bank_transfer', 'qris', 'ewallet', 'gateway', 'cash', 'edc'])->change();
            $table->unsignedInteger('received_amount')->nullable()->after('amount')->comment('uang diterima (tunai)');
            $table->unsignedInteger('change_amount')->nullable()->after('received_amount')->comment('kembalian');
            $table->string('external_reference', 100)->nullable()->after('gateway_transaction_id')
                ->comment('kode approval EDC / referensi QRIS dari struk mesin');
        });
    }

    public function down(): void
    {
        if (DB::table('payments')->whereIn('method', ['cash', 'edc'])->exists() || DB::table('bookings')->where('channel', 'pos')->exists()) {
            throw new RuntimeException('Rollback dibatalkan: sudah ada transaksi POS. Ekspor/hapus data POS secara manual terlebih dahulu.');
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['received_amount', 'change_amount', 'external_reference']);
            $table->enum('method', ['bank_transfer', 'qris', 'ewallet', 'gateway'])->change();
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['channel', 'visit_date']);
            $table->dropConstrainedForeignId('sold_by');
            $table->dropConstrainedForeignId('pos_session_id');
            $table->dropColumn('channel');
        });

        Schema::dropIfExists('pos_sessions');
    }
};
