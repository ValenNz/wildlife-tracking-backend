<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TrackingDevice;

class DeviceController extends Controller
{
    public function index()
    {
        $devices = TrackingDevice::with('animal.species')->get();

        return response()->json([
            'success' => true,
            'data' => $devices,
        ]);
    }

    public function show($id)
    {
        $device = TrackingDevice::with(['animal.species', 'gpsReadings'])
            ->find($id);

        if (! $device) {
            return response()->json(['error' => 'Device not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $device,
        ]);
    }
}
