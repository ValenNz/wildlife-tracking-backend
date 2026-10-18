<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Animal;
use App\Models\GeoZone;
use App\Models\GPSReadings;
use App\Models\Notification;
use App\Models\Species;
use App\Models\TrackingDevice;

class DashboardController extends Controller
{
    public function summary()
    {
        return response()->json([
            'active_animals' => Animal::where('is_active', true)->count(),
            'total_animals' => Animal::count(),
            'total_species' => Species::count(),
            'gps_readings_today' => GPSReadings::whereDate('recorded_at', today())->count(),
            'total_gps_readings' => GPSReadings::count(),
            'protected_zones' => GeoZone::count(),
            'active_devices' => TrackingDevice::where('status', 'Active')->count(),
            'devices_in_maintenance' => TrackingDevice::where('status', 'Maintenance')->count(),
            'devices_offline' => TrackingDevice::where('status', 'Inactive')->count(),
            'recent_notifications' => Notification::orderBy('created_at', 'desc')->limit(5)->get(),
        ]);
    }
}
