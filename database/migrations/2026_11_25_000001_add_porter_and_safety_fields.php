<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tour_routes', function (Blueprint $table) {
            $table->unsignedInteger('porter_fee_per_day')->default(150000)->after('guide_fee_per_person');
            $table->json('emergency_contacts')->nullable()->after('waypoints');
            $table->json('post_details')->nullable()->after('emergency_contacts');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->unsignedTinyInteger('porter_count')->default(0)->after('drone_count');
        });
    }

    public function down(): void
    {
        Schema::table('tour_routes', function (Blueprint $table) {
            $table->dropColumn(['porter_fee_per_day', 'emergency_contacts', 'post_details']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('porter_count');
        });
    }
};
