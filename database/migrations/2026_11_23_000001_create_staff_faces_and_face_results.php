<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_faces', function (Blueprint $table) {
            $table->id('staff_face_id');
            $table->string('name', 100)->comment('nama staf yang ditampilkan');
            $table->foreignId('user_id')->nullable()->constrained('users', 'user_id')->cascadeOnUpdate()->nullOnDelete();
            $table->string('model', 100);
            $table->json('vector');
            $table->json('bbox')->nullable()->comment('[x, y, lebar, tinggi] relatif 0-1');
            $table->decimal('detection_confidence', 5, 4)->nullable();
            $table->string('file_path', 500)->nullable()->comment('foto wajah di storage privat');
            $table->foreignId('created_by')->nullable()->constrained('users', 'user_id')->cascadeOnUpdate()->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index('name');
        });

        Schema::table('ai_predictions', function (Blueprint $table) {
            $table->json('faces')->nullable()->after('detected_objects')
                ->comment('[{status: staff|possible_staff|unknown, name, similarity, bbox, time_seconds}]');
            $table->unsignedSmallInteger('staff_face_count')->default(0)->after('faces');
            $table->unsignedSmallInteger('unknown_face_count')->default(0)->after('staff_face_count');
        });
    }

    public function down(): void
    {
        Schema::table('ai_predictions', function (Blueprint $table) {
            $table->dropColumn(['faces', 'staff_face_count', 'unknown_face_count']);
        });
        Schema::dropIfExists('staff_faces');
    }
};
