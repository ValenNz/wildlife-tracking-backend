<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\GuideShift;
use App\Models\Payment;
use App\Models\User;
use App\Services\Tourism\TourismClock;
use Tests\TestCase;

class PaymentSimulatorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAll();
        config(['services.tourism.payment_simulator' => true]);
    }

    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $this->app['auth']->forgetGuards();

        return parent::call($method, $uri, $parameters, $cookies, $files, $server, $content);
    }

    private function createBooking(array $headers): string
    {
        $shift = GuideShift::whereDate('shift_date', '>', TourismClock::today())
            ->orderBy('shift_date')
            ->firstOrFail();

        return $this->postJson('/api/visitor/bookings', [
            'route_id' => $shift->route_id,
            'shift_id' => $shift->shift_id,
            'wni_count' => 1,
            'wna_count' => 0,
            'student_count' => 0,
            'contact_name' => 'Budi',
            'contact_phone' => '081234567890',
            'emergency_contact' => 'Ani 0812',
            'payment_method' => 'bank_transfer',
            'participants' => [
                ['full_name' => 'Budi', 'visitor_type' => 'wni'],
            ],
        ], $headers)->assertCreated()->json('data.booking_code');
    }

    public function test_simulator_disabled_returns_503(): void
    {
        config(['services.tourism.payment_simulator' => false]);
        $headers = $this->loginAs('pengunjung');
        $code = $this->createBooking($headers);

        $this->getJson('/api/tourism/payment-info')
            ->assertOk()
            ->assertJsonPath('data.simulator_enabled', false);

        $this->postJson("/api/visitor/bookings/{$code}/simulate-payment", [
            'channel' => 'qris',
            'outcome' => 'paid',
        ], $headers)->assertStatus(503);
    }

    public function test_successful_simulated_payment_issues_ticket_automatically(): void
    {
        $headers = $this->loginAs('pengunjung');
        $code = $this->createBooking($headers);

        $this->getJson('/api/tourism/payment-info')
            ->assertOk()
            ->assertJsonPath('data.simulator_enabled', true)
            ->assertJsonPath('data.gateway_enabled', true);

        $response = $this->postJson("/api/visitor/bookings/{$code}/simulate-payment", [
            'channel' => 'qris',
            'outcome' => 'paid',
        ], $headers)->assertOk()
            ->assertJsonPath('data.booking.status', 'paid')
            ->assertJsonPath('data.simulation.channel', 'qris')
            ->assertJsonPath('data.simulation.outcome', 'paid')
            ->assertJsonPath('data.simulation.verified_automatically', true);

        $txId = $response->json('data.simulation.transaction_id');
        $this->assertStringStartsWith('SIM-QRIS-', (string) $txId);

        $booking = Booking::where('booking_code', $code)->firstOrFail();
        $this->assertSame('paid', $booking->status);
        $this->assertNotNull($booking->ticket_token_hash);

        $gatewayPayment = Payment::where('booking_id', $booking->booking_id)
            ->where('method', 'gateway')
            ->firstOrFail();
        $this->assertSame('paid', $gatewayPayment->status);
        $this->assertNull($gatewayPayment->verified_by);
        $this->assertSame($txId, $gatewayPayment->gateway_transaction_id);
        $this->assertSame('qris', $gatewayPayment->external_reference);

        $this->assertSame(
            'expired',
            Payment::where('booking_id', $booking->booking_id)->where('method', 'bank_transfer')->value('status')
        );

        $this->getJson("/api/visitor/bookings/{$code}/ticket", $headers)->assertOk();

        $other = User::factory()->create([
            'role_id' => User::where('email', 'pengunjung@wildlife.id')->value('role_id'),
            'status' => 'aktif',
        ]);
        $otherToken = $other->createToken('test')->plainTextToken;
        $this->postJson("/api/visitor/bookings/{$code}/simulate-payment", [
            'channel' => 'qris',
            'outcome' => 'paid',
        ], ['Authorization' => "Bearer {$otherToken}"])->assertNotFound();
    }

    public function test_failed_simulated_payment_keeps_booking_pending_until_retried(): void
    {
        $headers = $this->loginAs('pengunjung');
        $code = $this->createBooking($headers);

        $this->postJson("/api/visitor/bookings/{$code}/simulate-payment", [
            'channel' => 'va_bca',
            'outcome' => 'failed',
        ], $headers)->assertOk()
            ->assertJsonPath('data.booking.status', 'pending_payment')
            ->assertJsonPath('data.simulation.outcome', 'failed')
            ->assertJsonPath('data.simulation.verified_automatically', false);

        $this->getJson("/api/visitor/bookings/{$code}/ticket", $headers)->assertStatus(409);

        $this->postJson("/api/visitor/bookings/{$code}/simulate-payment", [
            'channel' => 'gopay',
            'outcome' => 'paid',
        ], $headers)->assertOk()
            ->assertJsonPath('data.booking.status', 'paid')
            ->assertJsonPath('data.simulation.verified_automatically', true);

        $this->getJson("/api/visitor/bookings/{$code}/ticket", $headers)->assertOk();
    }
}
