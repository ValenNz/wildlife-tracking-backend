<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictDeviceTokens
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->user()?->currentAccessToken();
        $abilities = $token?->abilities ?? [];
        $device = collect($abilities)->first(fn ($ability) => is_string($ability) && str_starts_with($ability, 'device:'));

        if ($device === null) {
            return $next($request);
        }

        $deviceId = (int) substr($device, 7);
        $deny = fn () => response()->json([
            'success' => false,
            'message' => 'Token perangkat hanya untuk mengirim data perangkat ini.',
            'data' => null,
            'errors' => null,
        ], 403);

        $path = '/'.ltrim(preg_replace('#^api/(v1/)?#', '', $request->path()), '/');

        if (preg_match('#^/(devices|tracking-devices)/(\d+)/(telemetry|captures)$#', $path, $m)) {
            return (int) $m[2] === $deviceId ? $next($request) : $deny();
        }

        if ($request->isMethod('post') && $path === '/gps-readings') {
            return (int) $request->input('device_id') === $deviceId ? $next($request) : $deny();
        }

        if ($request->isMethod('post') && $path === '/gps-readings/bulk') {
            $ids = collect($request->input('readings', []))->pluck('device_id')->map(fn ($id) => (int) $id)->unique();

            return $ids->count() === 1 && $ids->first() === $deviceId ? $next($request) : $deny();
        }

        return $deny();
    }
}
