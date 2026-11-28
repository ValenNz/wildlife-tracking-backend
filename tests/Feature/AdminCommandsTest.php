<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class AdminCommandsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAll();
    }

    public function test_reset_default_passwords_changes_seed_passwords_and_revokes_tokens(): void
    {
        $headers = $this->loginAs('admin');
        $this->artisan('security:check')->assertExitCode(1);

        $this->artisan('users:reset-default-passwords')->assertSuccessful();

        $admin = User::where('username', 'admin')->first();
        $this->assertFalse(Hash::check('password123', $admin->password));
        $this->assertSame(0, $admin->tokens()->count());
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/me', $headers)->assertUnauthorized();
        $this->artisan('security:check')->assertExitCode(0);
    }

    public function test_prune_idle_tokens_removes_only_expired_ones(): void
    {
        $this->loginAs('admin');
        $this->loginAs('peneliti');
        $this->travel(3)->hours();
        $this->loginAs('lapangan');

        $this->artisan('tokens:prune-idle')->assertSuccessful();
        $this->assertSame(1, PersonalAccessToken::count());
    }
}
