<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activitylog', function (Blueprint $table) {
            $table->renameColumn('created_at', 'timestamp');
            $table->timestamp('timestamp')->useCurrent()->change();
        });
    }

    public function down(): void
    {
        Schema::table('activitylog', function (Blueprint $table) {
            $table->renameColumn('timestamp', 'created_at');
        });
    }
};