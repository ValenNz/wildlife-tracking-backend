<?php

namespace Tests\Feature;

use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TrustedProxyTest extends TestCase
{
    protected function tearDown(): void
    {
        TrustProxies::flushState();
        parent::tearDown();
    }

    private function clientIp(array $server): string
    {
        Route::get('/_test/ip', fn () => response()->json(['ip' => request()->ip()]));

        return $this->call('GET', '/_test/ip', server: $server)->json('ip');
    }

    public function test_forwarded_header_is_ignored_without_trusted_proxy(): void
    {
        $this->assertSame('10.0.0.9', $this->clientIp(['REMOTE_ADDR' => '10.0.0.9', 'HTTP_X_FORWARDED_FOR' => '1.2.3.4']));
    }

    public function test_forwarded_header_is_used_from_trusted_proxy_only(): void
    {
        TrustProxies::at(['127.0.0.1']);
        TrustProxies::withHeaders(Request::HEADER_X_FORWARDED_FOR);

        $this->assertSame('36.80.1.2', $this->clientIp(['REMOTE_ADDR' => '127.0.0.1', 'HTTP_X_FORWARDED_FOR' => '36.80.1.2']));
        $this->assertSame('36.80.1.2', $this->clientIp(['REMOTE_ADDR' => '127.0.0.1', 'HTTP_X_FORWARDED_FOR' => '9.9.9.9, 36.80.1.2']));
        $this->assertSame('203.0.113.5', $this->clientIp(['REMOTE_ADDR' => '203.0.113.5', 'HTTP_X_FORWARDED_FOR' => '1.2.3.4']));
    }
}
