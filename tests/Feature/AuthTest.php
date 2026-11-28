<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\TestCase;

class AuthTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAll();
    }

    public function test_login_returns_shape_expected_by_frontend(): void
    {
        $this->postJson('/api/login', ['email' => 'admin@wildlife.id', 'password' => 'password123'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.username', 'admin')
            ->assertJsonPath('data.user.full_name', 'Admin Sistem')
            ->assertJsonPath('data.user.role_id', $this->idOf('Role', 'name_role', 'administrator_sistem'))
            ->assertJsonPath('data.user.name_role', 'administrator_sistem')
            ->assertJsonPath('data.user.role.role_name', 'administrator_sistem')
            ->assertJsonMissingPath('data.user.password')
            ->assertJsonStructure(['data' => ['token', 'user' => ['user_id', 'email', 'status', 'permissions']]]);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $this->postJson('/api/login', ['email' => 'admin@wildlife.id', 'password' => 'salah'])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['email']]);
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::where('username', 'lapangan')->update(['status' => 'nonaktif']);

        $this->postJson('/api/login', ['email' => 'lapangan@wildlife.id', 'password' => 'password123'])->assertForbidden();
    }

    public function test_me_and_logout(): void
    {
        $headers = $this->loginAs('peneliti');

        $this->getJson('/api/me', $headers)->assertOk()->assertJsonPath('data.role.role_name', 'peneliti_ekologi');
        $this->getJson('/api/v1/auth/me', $headers)->assertOk();
        $this->postJson('/api/logout', [], $headers)->assertOk();

        $this->app['auth']->forgetGuards();
        $this->getJson('/api/me', $headers)->assertUnauthorized();
    }

    public function test_public_registration_only_creates_visitor_accounts(): void
    {
        $res = $this->postJson('/api/register', $this->withCaptcha([
            'full_name' => 'Tamu', 'username' => 'tamu', 'email' => 'tamu@contoh.id',
            'password' => 'rahasia123', 'password_confirmation' => 'rahasia123',
            'role' => 'administrator_sistem',
        ]))->assertCreated();

        $this->assertSame('pengunjung', $res->json('data.user.name_role'));
        $this->assertNotContains('view_data', $res->json('data.user.permissions'));
    }

    public function test_registration_captcha_blocks_bots(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);
        $base = ['full_name' => 'Bot', 'username' => 'bot1', 'email' => 'bot@contoh.id', 'password' => 'rahasia123', 'password_confirmation' => 'rahasia123'];

        $this->postJson('/api/register', $base)->assertUnprocessable()->assertJsonValidationErrors('captcha_token');
        $wrong = $this->withCaptcha($base);
        $this->postJson('/api/register', ['captcha_answer' => $wrong['captcha_answer'] + 1] + $wrong)->assertUnprocessable()->assertJsonValidationErrors('captcha_answer');
        $this->postJson('/api/register', ['captcha_token' => 'eyJhIjoxfQ.palsu'] + $this->withCaptcha($base))->assertUnprocessable();
        $this->postJson('/api/register', $this->withCaptcha($base + ['website' => 'http://spam.example']))->assertUnprocessable()->assertJsonValidationErrors('website');

        $ok = $this->withCaptcha($base);
        $this->postJson('/api/register', $ok)->assertCreated();
        $this->postJson('/api/register', ['username' => 'bot2', 'email' => 'bot2@contoh.id'] + $ok)->assertUnprocessable()->assertJsonValidationErrors('captcha_answer');

        config(['services.registration.captcha_min_seconds' => 5]);
        $this->postJson('/api/register', $this->withCaptcha(['username' => 'bot3', 'email' => 'bot3@contoh.id'] + $base))
            ->assertUnprocessable()->assertJsonValidationErrors('captcha_answer');
    }

    public function test_change_password(): void
    {
        $headers = $this->loginAs('lapangan');

        $this->putJson('/api/me/password', [
            'old_password' => 'password123',
            'new_password' => 'rahasiaBaru1',
            'new_password_confirmation' => 'rahasiaBaru1',
        ], $headers)->assertOk();

        $this->postJson('/api/login', ['email' => 'lapangan@wildlife.id', 'password' => 'rahasiaBaru1'])->assertOk();
    }
}
