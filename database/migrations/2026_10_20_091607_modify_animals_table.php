<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            $table->renameColumn('animal_code', 'name');
            $table->dropColumn(['gender', 'created_at', 'updated_at']);
            $table->enum('gender', ['male', 'female', 'unknown'])->default('unknown')->after('birth_date');
        });
    }

    public function down(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            $table->renameColumn('name', 'animal_code');
            $table->dropColumn('gender');
            $table->enum('gender', ['Jantan', 'Betina', 'Tidak Diketahui'])->default('Tidak Diketahui')->after('birth_date');
            $table->timestamps();
        });
    }
};