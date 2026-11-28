<?php

namespace Tests\Feature;

use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAll();
    }

    public function test_api_responses_include_security_headers(): void
    {
        $response = $this->getJson('/api/tourism/payment-info');

        $response->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'no-referrer');
    }

    public function test_payment_simulator_is_blocked_in_production_environment(): void
    {
        $headers = $this->loginAs('pengunjung');
        config()->set('services.tourism.payment_simulator', true);
        $this->app->detectEnvironment(fn () => 'production');

        $this->getJson('/api/tourism/payment-info')
            ->assertOk()
            ->assertJsonPath('data.simulator_enabled', false);

        $this->postJson('/api/visitor/bookings/NONEXISTENT/simulate-payment', [
            'channel' => 'qris',
        ], $headers)->assertStatus(503);
    }

    public function test_unauthenticated_requests_to_protected_endpoints_return_401(): void
    {
        $this->getJson('/api/dashboard')->assertUnauthorized();
        $this->getJson('/api/users')->assertUnauthorized();
        $this->getJson('/api/notifications')->assertUnauthorized();
    }

    public function test_visitor_cannot_access_staff_or_admin_endpoints(): void
    {
        $headers = $this->loginAs('pengunjung');

        $this->getJson('/api/users', $headers)->assertForbidden();
        $this->getJson('/api/pos/sales', $headers)->assertForbidden();
        $this->getJson('/api/ai/threats', $headers)->assertForbidden();
    }
}
