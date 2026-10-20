<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('animalgeozones', function (Blueprint $table) {
            $table->dropForeign(['exit_reading_id']);
            $table->dropColumn(['exit_reading_id', 'duration_hours']);
            $table->timestamp('entered_at')->after('entry_reading_id');
            $table->timestamp('exited_at')->nullable()->after('entered_at');
        });
    }

    public function down(): void
    {
        Schema::table('animalgeozones', function (Blueprint $table) {
            $table->dropColumn(['entered_at', 'exited_at']);
            $table->foreignId('exit_reading_id')->nullable()->constrained('gpsreadings', 'reading_id')->nullOnDelete()->after('entry_reading_id');
            $table->decimal('duration_hours', 8, 2)->nullable()->after('exit_reading_id');
        });
    }
};