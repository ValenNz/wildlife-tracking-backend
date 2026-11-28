<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_prices', function (Blueprint $table) {
            $table->id('price_id');
            $table->string('code', 50)->unique()->comment('mis. entry_wni_weekday');
            $table->string('name', 150);
            $table->enum('category', ['entry', 'activity'])->comment('entry = tiket masuk per orang; activity = kegiatan tambahan');
            $table->enum('visitor_type', ['wni', 'wna', 'pelajar', 'any'])->default('any');
            $table->enum('day_type', ['weekday', 'holiday', 'any'])->default('any');
            $table->string('unit', 50)->comment('orang/hari, unit/hari');
            $table->unsignedInteger('amount')->comment('Rupiah');
            $table->unsignedSmallInteger('min_quantity')->default(1)->comment('mis. pelajar minimal 5 orang');
            $table->string('legal_basis', 150)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('public_holidays', function (Blueprint $table) {
            $table->id('holiday_id');
            $table->date('holiday_date')->unique();
            $table->string('name', 150);
            $table->timestamps();
        });

        Schema::create('tour_routes', function (Blueprint $table) {
            $table->id('route_id');
            $table->string('code', 30)->unique();
            $table->string('slug', 120)->unique();
            $table->string('name', 150);
            $table->string('entry_point', 100)->comment('pintu masuk, mis. Bukit Lawang');
            $table->text('summary');
            $table->text('description');
            $table->enum('difficulty', ['mudah', 'sedang', 'sulit']);
            $table->unsignedSmallInteger('duration_hours');
            $table->unsignedTinyInteger('duration_days')->default(1)->comment('dipakai untuk tiket masuk per hari');
            $table->decimal('distance_km', 6, 2)->nullable();
            $table->unsignedSmallInteger('max_group_size')->default(8);
            $table->unsignedTinyInteger('min_age')->default(0);
            $table->string('meeting_point', 200);
            $table->decimal('meeting_lat', 10, 7)->nullable();
            $table->decimal('meeting_lon', 10, 7)->nullable();
            $table->time('start_time');
            $table->unsignedInteger('guide_fee_per_person')->comment('jasa pemandu per orang (Rupiah), bukan PNBP');
            $table->json('itinerary')->comment('[{time, title, description}]');
            $table->json('includes')->comment('yang termasuk paket');
            $table->json('bring_list')->comment('kelengkapan yang harus dibawa pengunjung');
            $table->json('rules')->comment('aturan selama tur');
            $table->json('wildlife')->comment('satwa yang mungkin terlihat');
            $table->json('waypoints')->nullable()->comment('[[lat, lon, elevasi_m]] untuk peta/rute 3D');
            $table->foreignId('zone_id')->nullable()->constrained('geo_zones', 'zone_id')->cascadeOnUpdate()->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('tour_guides', function (Blueprint $table) {
            $table->id('guide_id');
            $table->foreignId('user_id')->nullable()->unique()->constrained('users', 'user_id')->cascadeOnUpdate()->nullOnDelete();
            $table->string('display_name', 100);
            $table->string('photo_path', 500)->nullable();
            $table->text('bio');
            $table->json('languages');
            $table->json('specialties');
            $table->string('favorite_animal', 100);
            $table->string('favorite_animal_reason', 255)->nullable();
            $table->unsignedTinyInteger('years_experience')->default(0);
            $table->string('certification', 150)->nullable();
            $table->string('fun_fact', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('guide_shifts', function (Blueprint $table) {
            $table->id('shift_id');
            $table->foreignId('guide_id')->constrained('tour_guides', 'guide_id')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('route_id')->constrained('tour_routes', 'route_id')->cascadeOnUpdate()->cascadeOnDelete();
            $table->date('shift_date');
            $table->enum('shift_type', ['pagi', 'siang', 'malam']);
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('capacity')->comment('maksimal pengunjung dalam shift ini');
            $table->enum('status', ['scheduled', 'cancelled'])->default('scheduled');
            $table->timestamps();

            $table->unique(['guide_id', 'shift_date', 'shift_type'], 'guide_shift_unique');
            $table->index(['route_id', 'shift_date']);
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id('booking_id');
            $table->string('booking_code', 20)->unique()->comment('acak, tidak berurutan');
            $table->foreignId('user_id')->constrained('users', 'user_id')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('route_id')->constrained('tour_routes', 'route_id')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('shift_id')->constrained('guide_shifts', 'shift_id')->cascadeOnUpdate()->restrictOnDelete();
            $table->date('visit_date');
            $table->enum('day_type', ['weekday', 'holiday']);
            $table->unsignedSmallInteger('wni_count')->default(0);
            $table->unsignedSmallInteger('wna_count')->default(0);
            $table->unsignedSmallInteger('student_count')->default(0);
            $table->unsignedTinyInteger('drone_count')->default(0);
            $table->unsignedSmallInteger('participant_count');
            $table->json('price_lines')->comment('rincian harga saat dipesan; tidak berubah walau tarif diubah');
            $table->unsignedInteger('total_amount');
            $table->enum('status', ['pending_payment', 'awaiting_verification', 'paid', 'checked_in', 'completed', 'cancelled', 'expired'])
                ->default('pending_payment');
            $table->timestamp('expires_at')->nullable()->comment('batas bayar');
            $table->string('contact_name', 100);
            $table->string('contact_phone', 30);
            $table->string('emergency_contact', 150);
            $table->text('notes')->nullable();
            $table->char('ticket_token_hash', 64)->nullable()->unique()->comment('sha256 token QR; token asli hanya ditampilkan ke pemilik');
            $table->timestamp('checked_in_at')->nullable();
            $table->foreignId('checked_in_by')->nullable()->constrained('users', 'user_id')->cascadeOnUpdate()->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['shift_id', 'status']);
        });

        Schema::create('booking_participants', function (Blueprint $table) {
            $table->id('participant_id');
            $table->foreignId('booking_id')->constrained('bookings', 'booking_id')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('full_name', 100);
            $table->enum('visitor_type', ['wni', 'wna', 'pelajar']);
            $table->unsignedTinyInteger('age')->nullable();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id('payment_id');
            $table->foreignId('booking_id')->constrained('bookings', 'booking_id')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('reference', 40)->unique()->comment('kode transaksi unik');
            $table->enum('method', ['bank_transfer', 'qris', 'ewallet', 'gateway']);
            $table->unsignedInteger('amount');
            $table->enum('status', ['pending', 'awaiting_verification', 'paid', 'rejected', 'expired', 'refunded'])->default('pending');
            $table->string('proof_path', 500)->nullable()->comment('bukti transfer di storage privat');
            $table->string('gateway_transaction_id', 100)->nullable()->unique();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users', 'user_id')->cascadeOnUpdate()->nullOnDelete();
            $table->string('rejection_reason', 255)->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        Schema::create('tour_reviews', function (Blueprint $table) {
            $table->id('review_id');
            $table->foreignId('booking_id')->unique()->constrained('bookings', 'booking_id')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users', 'user_id')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('route_id')->constrained('tour_routes', 'route_id')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('guide_id')->nullable()->constrained('tour_guides', 'guide_id')->cascadeOnUpdate()->nullOnDelete();
            $table->unsignedTinyInteger('route_rating');
            $table->unsignedTinyInteger('guide_rating')->nullable();
            $table->text('comment')->nullable();
            $table->boolean('is_visible')->default(true)->comment('admin bisa menyembunyikan ulasan tidak pantas');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tour_reviews');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('booking_participants');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('guide_shifts');
        Schema::dropIfExists('tour_guides');
        Schema::dropIfExists('tour_routes');
        Schema::dropIfExists('public_holidays');
        Schema::dropIfExists('ticket_prices');
    }
};
