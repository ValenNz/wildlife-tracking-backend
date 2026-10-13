<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GPSReadings;
use Illuminate\Http\Request;

class GpsReadingController extends Controller
{
    public function index()
    {
        $readings = GPSReadings::with('trackingDevice.animal.species')
            ->orderBy('recorded_at', 'desc')
            ->limit(100)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $readings,
            'total' => $readings->count()
        ]);
    }

    public function byAnimal($animal_id)
    {
        $readings = GPSReadings::whereHas('trackingDevice', function($query) use ($animal_id) {
            $query->where('animal_id', $animal_id);
        })
        ->orderBy('recorded_at', 'desc')
        ->get();

        return response()->json([
            'success' => true,
            'data' => $readings,
            'total' => $readings->count()
        ]);
    }
}