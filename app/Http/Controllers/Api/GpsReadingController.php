<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Animal, GpsReading, Geozones};
use Illuminate\Http\Request;

class GpsReadingController extends Controller
{
    public function livePosition($animalId)
    {
        $animal = Animal::with(['trackingDevice', 'species'])->findOrFail($animalId);
        $device = $animal->trackingDevice;

        $latestReading = GpsReading::where('device_id', $device->device_id)
            ->orderBy('recorded_at', 'DESC')
            ->first();

        if (!$latestReading) {
            return response()->json(['message' => 'No GPS data available'], 404);
        }

        return response()->json([
            'animal' => $animal,
            'position' => $latestReading,
            'device_status' => [
                'battery' => $device->battery_level,
                'signal' => $device->signal_strength,
                'last_update' => $device->last_recorded_at
            ]
        ]);
    }

    public function history($animalId, Request $request)
    {
        $animal = Animal::findOrFail($animalId);
        $device = $animal->trackingDevice;

        $readings = GpsReading::where('device_id', $device->device_id)
            ->when($request->start_date, fn($q, $d) => $q->where('recorded_at', '>=', $d))
            ->when($request->end_date, fn($q, $d) => $q->where('recorded_at', '<=', $d))
            ->orderBy('recorded_at')
            ->get();

        return response()->json(['data' => $readings]);
    }
}