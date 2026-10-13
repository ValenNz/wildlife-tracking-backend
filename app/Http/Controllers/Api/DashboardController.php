<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Animal, GpsReading, Geozones, TrackingDevice, Notification};

class DashboardController extends Controller
{
    public function summary()
    {
        return response()->json([
            'active_animals' => Animal::where('is_active', true)->count(),
            'total_animals' => Animal::count(),
            'gps_readings_today' => GpsReading::whereDate('recorded_at', today())->count(),
            'total_gps_readings' => GpsReading::count(),
            'protected_zones' => Geozones::count(),
            'active_devices' => TrackingDevice::where('is_active', true)->count(),
            'device_alerts' => TrackingDevice::where(function($q) {
                $q->where('battery_level', '<', 20)
                  ->orWhere('signal_strength', '<', 3);
            })->get(['device_id', 'animal_id', 'battery_level', 'signal_strength']),
            'recent_notifications' => Notification::orderBy('created_at', 'desc')->limit(5)->get(),
        ]);
    }
}