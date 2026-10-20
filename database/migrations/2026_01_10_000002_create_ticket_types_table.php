<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_types', function (Blueprint $table) {
            $table->unsignedInteger('ticket_type_id')->primary();
            $table->string('type_name', 100)->unique();
            $table->string('description', 255)->nullable();
            $table->decimal('price', 12, 2)->comment('harga per tiket (IDR)');
            $table->boolean('is_active')->default(1);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_types');
    }
};