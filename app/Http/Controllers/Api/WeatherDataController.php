<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WeatherDataResource;
use App\Models\WeatherData;
use Illuminate\Http\Request;

class WeatherDataController extends Controller
{
    public function index()
    {
        $weather = WeatherData::with(['gpsReading'])->get();
        return WeatherDataResource::collection($weather);
    }

    public function show($id)
    {
        $weather = WeatherData::with(['gpsReading'])->find($id);
        if (!$weather) {
            return response()->json(['message' => 'WeatherData not found'], 404);
        }
        return new WeatherDataResource($weather);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'reading_id' => 'nullable|integer|exists:gps_readings,reading_id',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'observed_at' => 'required|date',
            'temperature_c' => 'nullable|numeric',
            'rainfall_mm' => 'nullable|numeric',
            'humidity_percent' => 'nullable|numeric',
            'pressure_hpa' => 'nullable|numeric',
            'source' => 'required|string|max:50',
        ]);
        $weather = WeatherData::create($validated);
        return new WeatherDataResource($weather);
    }

    public function update(Request $request, $id)
    {
        $weather = WeatherData::find($id);
        if (!$weather) {
            return response()->json(['message' => 'WeatherData not found'], 404);
        }
        $validated = $request->validate([
            'reading_id' => 'nullable|integer|exists:gps_readings,reading_id',
            'latitude' => 'sometimes|numeric',
            'longitude' => 'sometimes|numeric',
            'observed_at' => 'sometimes|date',
            'temperature_c' => 'nullable|numeric',
            'rainfall_mm' => 'nullable|numeric',
            'humidity_percent' => 'nullable|numeric',
            'pressure_hpa' => 'nullable|numeric',
            'source' => 'sometimes|string|max:50',
        ]);
        $weather->update($validated);
        return new WeatherDataResource($weather);
    }

    public function destroy($id)
    {
        $weather = WeatherData::find($id);
        if (!$weather) {
            return response()->json(['message' => 'WeatherData not found'], 404);
        }
        $weather->delete();
        return response()->json(['message' => 'WeatherData deleted']);
    }
}
