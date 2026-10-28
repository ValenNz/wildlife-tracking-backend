<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('user_id');
            $table->string('name', 100);
            $table->string('email', 150)->unique('uq_users_email');
            $table->string('password', 255);
            $table->enum('role', ['konservasionis_lapangan', 'peneliti_ekologi', 'administrator_sistem', 'pengambil_kebijakan']);
            $table->dateTime('created_at')->useCurrent();
            $table->index('role', 'idx_users_role');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
