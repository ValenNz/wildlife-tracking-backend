<?php

namespace Tests;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected bool $seed = false;

    protected function withCaptcha(array $payload): array
    {
        $challenge = $this->getJson('/api/register/challenge')->assertOk()->json('data');
        preg_match('/(\d+) ([+-]) (\d+)/', $challenge['question'], $m);
        $answer = $m[2] === '+' ? (int) $m[1] + (int) $m[3] : (int) $m[1] - (int) $m[3];

        return $payload + ['captcha_token' => $challenge['challenge_token'], 'captcha_answer' => $answer];
    }

    protected function seedAll(): void
    {
        $this->seed(DatabaseSeeder::class);
    }

    protected function idOf(string $model, string $column, string $value): int
    {
        $class = "App\\Models\\{$model}";

        return (int) $class::query()->where($column, $value)->value((new $class)->getKeyName());
    }

    protected function loginAs(string $username): array
    {
        $email = User::where('username', $username)->value('email');
        $token = $this->postJson('/api/login', ['email' => $email, 'password' => 'password123'])
            ->assertOk()
            ->json('data.token');

        $this->app['auth']->forgetGuards();

        return ['Authorization' => "Bearer {$token}", 'Accept' => 'application/json'];
    }
}
