<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\GuideShift;
use App\Models\Payment;
use App\Models\TourRoute;
use App\Models\User;
use App\Services\Tourism\BookingService;
use App\Services\Tourism\TourismClock;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TourismTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAll();
        Storage::fake('local');
        config(['services.tourism.webhook_secret' => '']);
    }

    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $this->app['auth']->forgetGuards();

        return parent::call($method, $uri, $parameters, $cookies, $files, $server, $content);
    }

    private function route(string $slug = 'bukit-lawang-orangutan-trek'): TourRoute
    {
        return TourRoute::where('slug', $slug)->firstOrFail();
    }

    private function shiftFor(TourRoute $route, ?string $date = null): GuideShift
    {
        return GuideShift::where('route_id', $route->route_id)
            ->when($date, fn ($q) => $q->whereDate('shift_date', $date), fn ($q) => $q->whereDate('shift_date', '>', TourismClock::today()))
            ->where('status', 'scheduled')->orderBy('shift_date')->firstOrFail();
    }

    private function bookingPayload(TourRoute $route, GuideShift $shift, array $overrides = []): array
    {
        return array_merge([
            'route_id' => $route->route_id,
            'shift_id' => $shift->shift_id,
            'wni_count' => 2,
            'wna_count' => 1,
            'contact_name' => 'Budi Santoso',
            'contact_phone' => '0812-3456-7890',
            'emergency_contact' => 'Ani (istri) 0813-1111-2222',
            'payment_method' => 'bank_transfer',
            'participants' => [
                ['full_name' => 'Budi Santoso', 'visitor_type' => 'wni', 'age' => 35],
                ['full_name' => 'Ani Santoso', 'visitor_type' => 'wni', 'age' => 33],
                ['full_name' => 'John Smith', 'visitor_type' => 'wna', 'age' => 40],
            ],
        ], $overrides);
    }

    private function registerVisitor(string $username = 'budi'): array
    {
        $token = $this->postJson('/api/register', $this->withCaptcha([
            'full_name' => 'Budi Santoso',
            'username' => $username,
            'email' => "{$username}@contoh.id",
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ]))->assertCreated()->json('data.token');
        $this->app['auth']->forgetGuards();

        return ['Authorization' => "Bearer {$token}", 'Accept' => 'application/json'];
    }

    public function test_registration_always_creates_visitor_role_without_access_to_conservation_data(): void
    {
        $admin = User::where('username', 'admin')->first();
        $res = $this->postJson('/api/register', $this->withCaptcha([
            'full_name' => 'Penyusup', 'username' => 'penyusup', 'email' => 'penyusup@contoh.id',
            'password' => 'rahasia123', 'password_confirmation' => 'rahasia123',
            'role_id' => $admin->role_id, 'status' => 'aktif',
        ]))->assertCreated();

        $this->assertSame('pengunjung', $res->json('data.user.name_role'));
        $this->assertSame(['book_tickets'], $res->json('data.user.permissions'));

        $h = ['Authorization' => 'Bearer '.$res->json('data.token'), 'Accept' => 'application/json'];
        $this->app['auth']->forgetGuards();
        foreach (['/api/animals', '/api/gps-readings', '/api/ai-predictions', '/api/users', '/api/tourism/admin/bookings'] as $url) {
            $this->getJson($url, $h)->assertForbidden();
        }
        $this->postJson('/api/tourism/check-in', ['ticket' => 'X.Y'], $h)->assertForbidden();
    }

    public function test_registration_requires_strong_password_and_unique_email(): void
    {
        $this->postJson('/api/register', $this->withCaptcha([
            'full_name' => 'A', 'username' => 'a1', 'email' => 'admin@wildlife.id',
            'password' => 'pendek', 'password_confirmation' => 'pendek',
        ]))->assertUnprocessable()->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_public_catalog_needs_no_login_and_hides_private_guide_data(): void
    {
        $this->getJson('/api/tourism/routes')->assertOk()->assertJsonCount(4, 'data');
        $this->getJson('/api/tourism/routes/bukit-lawang-orangutan-trek')
            ->assertOk()
            ->assertJsonPath('data.route.name', 'Trek Orangutan Bukit Lawang')
            ->assertJsonStructure(['data' => ['route' => ['itinerary', 'bring_list', 'rules', 'wildlife'], 'upcoming_shifts', 'reviews']]);

        $guides = $this->getJson('/api/tourism/guides')->assertOk()->json('data');
        $this->assertArrayHasKey('favorite_animal', $guides[0]);
        $this->assertArrayNotHasKey('user_id', $guides[0]);
        $this->assertArrayNotHasKey('photo_path', $guides[0]);

        $schedule = $this->getJson('/api/tourism/schedule')->assertOk()->json('data');
        $this->assertNotEmpty($schedule);
        $this->assertArrayHasKey('remaining', $schedule[0]);
        $this->assertArrayHasKey('favorite_animal', $schedule[0]['guide']);
    }

    public function test_quote_uses_pp_36_2024_rates_and_holiday_rate_on_sunday(): void
    {
        $route = $this->route();
        $monday = now()->next('Monday')->toDateString();
        $sunday = now()->next('Sunday')->toDateString();

        $weekday = $this->postJson('/api/tourism/quote', ['route_id' => $route->route_id, 'visit_date' => $monday, 'wni_count' => 2, 'wna_count' => 1])
            ->assertOk()->json('data');
        $this->assertSame('weekday', $weekday['day_type']);
        $this->assertSame(2 * 10000 + 150000 + 3 * 350000, $weekday['total']);

        $holiday = $this->postJson('/api/tourism/quote', ['route_id' => $route->route_id, 'visit_date' => $sunday, 'wni_count' => 2])
            ->assertOk()->json('data');
        $this->assertSame('holiday', $holiday['day_type']);
        $this->assertSame(2 * 15000 + 2 * 350000, $holiday['total']);
    }

    public function test_student_rate_requires_group_of_five_and_multi_day_route_charges_each_day(): void
    {
        $route = $this->route();
        $date = now()->next('Tuesday')->toDateString();
        $this->postJson('/api/tourism/quote', ['route_id' => $route->route_id, 'visit_date' => $date, 'student_count' => 3])
            ->assertUnprocessable()->assertJsonValidationErrors('student_count');

        $camp = $this->route('bukit-lawang-jungle-camp');
        $total = $this->postJson('/api/tourism/quote', ['route_id' => $camp->route_id, 'visit_date' => $date, 'wna_count' => 1])
            ->assertOk()->json('data.total');
        $this->assertSame(150000 * 2 + 900000, $total);
    }

    public function test_booking_total_is_computed_by_server_and_status_cannot_be_injected(): void
    {
        $h = $this->registerVisitor();
        $route = $this->route();
        $shift = $this->shiftFor($route);

        $res = $this->postJson('/api/visitor/bookings', $this->bookingPayload($route, $shift, [
            'total_amount' => 1, 'status' => 'paid', 'price_lines' => [],
        ]), $h)->assertCreated();

        $this->assertSame('pending_payment', $res->json('data.status'));
        $this->assertGreaterThan(1000000, $res->json('data.total_amount'));
        $this->assertMatchesRegularExpression('/^TNGL-[A-Z2-9]{8}$/', $res->json('data.booking_code'));
        $this->assertArrayNotHasKey('ticket_token_hash', $res->json('data'));
        $this->assertSame('pending', $res->json('data.payments.0.status'));
    }

    public function test_participant_names_must_match_ticket_counts(): void
    {
        $h = $this->registerVisitor();
        $route = $this->route();
        $this->postJson('/api/visitor/bookings', $this->bookingPayload($route, $this->shiftFor($route), ['wni_count' => 3]), $h)
            ->assertUnprocessable()->assertJsonValidationErrors('participants');
    }

    public function test_capacity_cannot_be_exceeded_and_expired_bookings_release_the_quota(): void
    {
        $h = $this->registerVisitor();
        $route = $this->route();
        $shift = $this->shiftFor($route);
        $shift->update(['capacity' => 4]);

        $first = $this->postJson('/api/visitor/bookings', $this->bookingPayload($route, $shift), $h)->assertCreated();
        $this->postJson('/api/visitor/bookings', $this->bookingPayload($route, $shift), $h)
            ->assertStatus(409)->assertJsonPath('message', 'Kuota jadwal ini tinggal 1 orang.');

        Booking::where('booking_code', $first->json('data.booking_code'))->update(['expires_at' => now()->subMinute()]);
        $this->postJson('/api/visitor/bookings', $this->bookingPayload($route, $shift), $h)->assertCreated();
        $this->assertSame('expired', Booking::where('booking_code', $first->json('data.booking_code'))->value('status'));
    }

    public function test_visitor_cannot_see_or_touch_another_visitors_booking(): void
    {
        $owner = $this->registerVisitor('pemilik');
        $route = $this->route();
        $code = $this->postJson('/api/visitor/bookings', $this->bookingPayload($route, $this->shiftFor($route)), $owner)
            ->assertCreated()->json('data.booking_code');

        $other = $this->registerVisitor('orang_lain');
        $this->getJson("/api/visitor/bookings/{$code}", $other)->assertNotFound();
        $this->postJson("/api/visitor/bookings/{$code}/cancel", [], $other)->assertNotFound();
        $this->getJson("/api/visitor/bookings/{$code}/ticket", $other)->assertNotFound();
        $this->getJson('/api/visitor/bookings', $other)->assertOk()->assertJsonCount(0, 'data');
    }

    private function paidBooking(array $visitor, ?string $date = null): Booking
    {
        $route = $this->route();
        $shift = $this->shiftFor($route, $date);
        $code = $this->postJson('/api/visitor/bookings', $this->bookingPayload($route, $shift), $visitor)->assertCreated()->json('data.booking_code');

        $this->post("/api/visitor/bookings/{$code}/payment-proof", ['file' => UploadedFile::fake()->image('bukti.jpg')], $visitor)
            ->assertOk()->assertJsonPath('data.status', 'awaiting_verification');

        $payment = Payment::whereHas('booking', fn ($q) => $q->where('booking_code', $code))->first();
        $admin = $this->loginAs('admin');
        $this->postJson("/api/tourism/admin/payments/{$payment->payment_id}/verify", ['approve' => true], $admin)
            ->assertOk()->assertJsonPath('data.status', 'paid');

        return Booking::where('booking_code', $code)->first();
    }

    public function test_proof_upload_only_accepts_images_or_pdf(): void
    {
        $h = $this->registerVisitor();
        $route = $this->route();
        $code = $this->postJson('/api/visitor/bookings', $this->bookingPayload($route, $this->shiftFor($route)), $h)->json('data.booking_code');

        $this->post("/api/visitor/bookings/{$code}/payment-proof", ['file' => UploadedFile::fake()->create('skrip.php', 10, 'text/x-php')], $h)
            ->assertUnprocessable();
    }

    public function test_only_staff_can_verify_payment_and_ticket_appears_after_payment(): void
    {
        $h = $this->registerVisitor();
        $route = $this->route();
        $code = $this->postJson('/api/visitor/bookings', $this->bookingPayload($route, $this->shiftFor($route)), $h)->json('data.booking_code');
        $this->getJson("/api/visitor/bookings/{$code}/ticket", $h)->assertStatus(409);

        $this->post("/api/visitor/bookings/{$code}/payment-proof", ['file' => UploadedFile::fake()->image('bukti.png')], $h)->assertOk();
        $payment = Payment::whereHas('booking', fn ($q) => $q->where('booking_code', $code))->first();
        $this->postJson("/api/tourism/admin/payments/{$payment->payment_id}/verify", ['approve' => true], $h)->assertForbidden();

        $this->postJson("/api/tourism/admin/payments/{$payment->payment_id}/verify", ['approve' => true], $this->loginAs('admin'))->assertOk();
        $ticket = $this->getJson("/api/visitor/bookings/{$code}/ticket", $h)->assertOk();
        $this->assertStringStartsWith($code.'.', $ticket->json('data.qr_payload'));
    }

    public function test_rejected_proof_gives_new_payment_window(): void
    {
        $h = $this->registerVisitor();
        $route = $this->route();
        $code = $this->postJson('/api/visitor/bookings', $this->bookingPayload($route, $this->shiftFor($route)), $h)->json('data.booking_code');
        $this->post("/api/visitor/bookings/{$code}/payment-proof", ['file' => UploadedFile::fake()->image('bukti.png')], $h)->assertOk();
        $payment = Payment::whereHas('booking', fn ($q) => $q->where('booking_code', $code))->first();

        $this->postJson("/api/tourism/admin/payments/{$payment->payment_id}/verify", ['approve' => false], $this->loginAs('admin'))
            ->assertUnprocessable()->assertJsonValidationErrors('reason');
        $this->postJson("/api/tourism/admin/payments/{$payment->payment_id}/verify", ['approve' => false, 'reason' => 'Nominal kurang'], $this->loginAs('admin'))
            ->assertOk();

        $this->assertSame('pending_payment', Booking::where('booking_code', $code)->value('status'));
        $this->post("/api/visitor/bookings/{$code}/payment-proof", ['file' => UploadedFile::fake()->image('bukti2.png')], $h)->assertOk();
    }

    public function test_paid_booking_can_only_be_cancelled_by_staff(): void
    {
        $h = $this->registerVisitor();
        $booking = $this->paidBooking($h);
        $this->postJson("/api/visitor/bookings/{$booking->booking_code}/cancel", [], $h)->assertStatus(409);

        $this->postJson("/api/tourism/admin/bookings/{$booking->booking_code}/cancel", [], $this->loginAs('admin'))->assertOk();
        $this->assertSame('refunded', $booking->payments()->value('status'));
    }

    public function test_check_in_validates_token_date_and_single_use(): void
    {
        $h = $this->registerVisitor();
        $booking = $this->paidBooking($h);
        $payload = app(BookingService::class)->ticketPayload($booking);
        $officer = $this->loginAs('lapangan');

        $this->postJson('/api/tourism/check-in', ['ticket' => $booking->booking_code.'.'.str_repeat('a', 32)], $officer)
            ->assertUnprocessable();
        $this->postJson('/api/tourism/check-in', ['ticket' => $payload], $officer)->assertStatus(409);

        $this->travelTo($booking->visit_date->copy()->setTime(9, 0));
        $officer = $this->loginAs('lapangan');
        $this->postJson('/api/tourism/check-in', ['ticket' => $payload], $officer)->assertOk()->assertJsonPath('data.status', 'checked_in');
        $this->postJson('/api/tourism/check-in', ['ticket' => $payload], $officer)->assertStatus(409);
    }

    public function test_review_only_after_check_in_and_once(): void
    {
        $h = $this->registerVisitor();
        $booking = $this->paidBooking($h);
        $code = $booking->booking_code;
        $this->postJson("/api/visitor/bookings/{$code}/review", ['route_rating' => 5], $h)->assertStatus(409);

        $booking->update(['status' => 'checked_in']);
        $this->postJson("/api/visitor/bookings/{$code}/review", ['route_rating' => 5, 'guide_rating' => 4, 'comment' => '<b>Orangutan</b> terlihat!'], $h)
            ->assertCreated()->assertJsonPath('data.comment', 'Orangutan terlihat!');
        $this->postJson("/api/visitor/bookings/{$code}/review", ['route_rating' => 1], $h)->assertStatus(409);

        $this->getJson('/api/tourism/routes/bukit-lawang-orangutan-trek')->assertJsonPath('data.reviews.0.reviewer', 'Budi S.');
    }

    public function test_payment_webhook_requires_valid_signature_and_matching_amount_and_is_idempotent(): void
    {
        $h = $this->registerVisitor();
        $route = $this->route();
        $code = $this->postJson('/api/visitor/bookings', $this->bookingPayload($route, $this->shiftFor($route)), $h)->json('data.booking_code');
        $payment = Payment::whereHas('booking', fn ($q) => $q->where('booking_code', $code))->first();
        $body = json_encode(['reference' => $payment->reference, 'status' => 'paid', 'amount' => $payment->amount, 'transaction_id' => 'GW-1']);

        $send = fn (string $json, string $signature) => $this->call('POST', '/api/payments/webhook', [], [], [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json', 'HTTP_X_SIGNATURE' => $signature], $json);

        $send($body, 'apa-saja')->assertStatus(503);

        config(['services.tourism.webhook_secret' => 'rahasia-gateway']);
        $send($body, hash_hmac('sha256', $body, 'salah'))->assertStatus(401);

        $wrongAmount = json_encode(['reference' => $payment->reference, 'status' => 'paid', 'amount' => 1, 'transaction_id' => 'GW-1']);
        $send($wrongAmount, hash_hmac('sha256', $wrongAmount, 'rahasia-gateway'))->assertUnprocessable();

        $send($body, hash_hmac('sha256', $body, 'rahasia-gateway'))->assertOk()->assertJsonPath('data.status', 'paid');
        $send($body, hash_hmac('sha256', $body, 'rahasia-gateway'))->assertOk()->assertJsonPath('data.status', 'paid');
        $this->assertSame(1, Payment::where('status', 'paid')->count());
    }
}
