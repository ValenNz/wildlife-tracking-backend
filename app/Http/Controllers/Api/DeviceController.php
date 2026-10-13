<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TrackingDevice;

class DeviceController extends Controller
{
    public function index()
    {
        $devices = TrackingDevice::with('animal.species')->get();
        return response()->json(['data' => $devices]);
    }

    public function alerts()
    {
        $alerts = TrackingDevice::with('animal.species')
            ->where(function($q) {
                $q->where('battery_level', '<', 20)
                  ->orWhere('signal_strength', '<', 3)
                  ->orWhere('is_active', false);
            })
            ->get();
        
        return response()->json(['data' => $alerts]);
    }
}