<?php

namespace App\Providers;

use App\Services\ApiSessionService;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        foreach (['id', 'animalId', 'deviceId', 'readingId', 'mediaId', 'permissionId', 'tokenId', 'embeddingId'] as $param) {
            Route::pattern($param, '[0-9]+');
        }

        $trusted = config('app.trusted_proxies', []);
        if ($trusted !== []) {
            TrustProxies::at($trusted);
            TrustProxies::withHeaders(Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PROTO);
        }

        Sanctum::authenticateAccessTokensUsing(
            fn (PersonalAccessToken $token, bool $isValid) => ApiSessionService::validate($token, $isValid)
        );
    }
}
