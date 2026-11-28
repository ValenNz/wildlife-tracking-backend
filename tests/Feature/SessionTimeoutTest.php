<?php

namespace Tests\Feature;

use App\Services\ApiSessionService;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class SessionTimeoutTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAll();
    }

    private function currentToken(): PersonalAccessToken
    {
        return PersonalAccessToken::latest('id')->firstOrFail();
    }

    public function test_login_records_activity_in_cache(): void
    {
        $this->loginAs('admin');

        $this->assertTrue(Cache::has(ApiSessionService::cacheKey($this->currentToken()->id)));
    }

    public function test_active_session_is_extended_on_each_request(): void
    {
        $headers = $this->loginAs('admin');
        $token = $this->currentToken();

        $this->travel(119)->minutes();
        $this->getJson('/api/me', $headers)->assertOk();
        $this->app['auth']->forgetGuards();

        $this->travel(119)->minutes();
        $this->getJson('/api/me', $headers)->assertOk();

        $this->assertNotNull(PersonalAccessToken::find($token->id));
    }

    public function test_idle_session_expires_after_two_hours(): void
    {
        $headers = $this->loginAs('admin');
        $token = $this->currentToken();

        $this->travel(121)->minutes();

        $this->getJson('/api/me', $headers)
            ->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', fn ($m) => str_contains($m, 'Sesi Anda telah berakhir'));

        $this->assertNull(PersonalAccessToken::find($token->id));
        $this->assertFalse(Cache::has(ApiSessionService::cacheKey($token->id)));
    }

    public function test_falls_back_to_last_used_at_when_cache_is_empty(): void
    {
        $headers = $this->loginAs('admin');
        $token = $this->currentToken();

        Cache::flush();
        $token->forceFill(['last_used_at' => now()->subHours(3)])->save();

        $this->getJson('/api/me', $headers)->assertStatus(401);
        $this->assertNull(PersonalAccessToken::find($token->id));
    }

    public function test_cache_miss_with_recent_db_activity_keeps_session(): void
    {
        $headers = $this->loginAs('admin');
        $token = $this->currentToken();

        Cache::flush();
        $token->forceFill(['last_used_at' => now()->subMinutes(30)])->save();

        $this->getJson('/api/me', $headers)->assertOk();
        $this->assertTrue(Cache::has(ApiSessionService::cacheKey($token->id)));
    }

    public function test_logout_clears_cached_session(): void
    {
        $headers = $this->loginAs('admin');
        $token = $this->currentToken();

        $this->postJson('/api/logout', [], $headers)->assertOk();

        $this->assertFalse(Cache::has(ApiSessionService::cacheKey($token->id)));
    }

    public function test_timeout_is_configurable(): void
    {
        config(['sanctum.idle_timeout' => 30]);
        $headers = $this->loginAs('admin');

        $this->travel(31)->minutes();

        $this->getJson('/api/me', $headers)
            ->assertStatus(401)
            ->assertJsonPath('message', fn ($m) => str_contains($m, '30 menit'));
    }
}
