<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GpsReadingResource;
use App\Models\GpsReading;
use Illuminate\Http\Request;

class GpsReadingController extends Controller
{
    public function index()
    {
        $readings = GpsReading::with(['trackingDevice'])->get();
        return GpsReadingResource::collection($readings);
    }

    public function show($id)
    {
        $reading = GpsReading::with(['trackingDevice', 'weatherData'])->find($id);
        if (!$reading) {
            return response()->json(['message' => 'GpsReading not found'], 404);
        }
        return new GpsReadingResource($reading);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'device_id' => 'required|integer|exists:tracking_devices,device_id',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'recorded_at' => 'required|date',
            'altitude_meters' => 'nullable|numeric',
            'accuracy_meters' => 'nullable|numeric',
            'checksum' => 'nullable|string|size:64',
        ]);
        $reading = GpsReading::create($validated);
        return new GpsReadingResource($reading);
    }

    public function update(Request $request, $id)
    {
        $reading = GpsReading::find($id);
        if (!$reading) {
            return response()->json(['message' => 'GpsReading not found'], 404);
        }
        $validated = $request->validate([
            'device_id' => 'sometimes|integer|exists:tracking_devices,device_id',
            'latitude' => 'sometimes|numeric',
            'longitude' => 'sometimes|numeric',
            'recorded_at' => 'sometimes|date',
            'altitude_meters' => 'nullable|numeric',
            'accuracy_meters' => 'nullable|numeric',
            'checksum' => 'nullable|string|size:64',
        ]);
        $reading->update($validated);
        return new GpsReadingResource($reading);
    }

    public function destroy($id)
    {
        $reading = GpsReading::find($id);
        if (!$reading) {
            return response()->json(['message' => 'GpsReading not found'], 404);
        }
        $reading->delete();
        return response()->json(['message' => 'GpsReading deleted']);
    }
}
