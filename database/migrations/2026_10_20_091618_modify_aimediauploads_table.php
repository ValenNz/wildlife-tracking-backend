<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aimediauploads', function (Blueprint $table) {
            $table->timestamp('uploaded_at')->useCurrent()->change();
        });
    }

    public function down(): void
    {
        Schema::table('aimediauploads', function (Blueprint $table) {
            $table->timestamp('uploaded_at')->change();
        });
    }
};