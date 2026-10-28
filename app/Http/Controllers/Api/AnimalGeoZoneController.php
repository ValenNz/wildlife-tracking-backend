<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AnimalGeoZoneResource;
use App\Models\AnimalGeoZone;
use Illuminate\Http\Request;

class AnimalGeoZoneController extends Controller
{
    public function index()
    {
        $visits = AnimalGeoZone::with(['animal', 'geoZone'])->get();
        return AnimalGeoZoneResource::collection($visits);
    }

    public function show($id)
    {
        $visit = AnimalGeoZone::with(['animal', 'geoZone', 'entryReading'])->find($id);
        if (!$visit) {
            return response()->json(['message' => 'AnimalGeoZone not found'], 404);
        }
        return new AnimalGeoZoneResource($visit);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'animal_id' => 'required|integer|exists:animals,animal_id',
            'zone_id' => 'required|integer|exists:geo_zones,zone_id',
            'entry_reading_id' => 'nullable|integer|exists:gps_readings,reading_id',
            'entered_at' => 'required|date',
            'exited_at' => 'nullable|date',
        ]);
        $visit = AnimalGeoZone::create($validated);
        return new AnimalGeoZoneResource($visit);
    }

    public function update(Request $request, $id)
    {
        $visit = AnimalGeoZone::find($id);
        if (!$visit) {
            return response()->json(['message' => 'AnimalGeoZone not found'], 404);
        }
        $validated = $request->validate([
            'animal_id' => 'sometimes|integer|exists:animals,animal_id',
            'zone_id' => 'sometimes|integer|exists:geo_zones,zone_id',
            'entry_reading_id' => 'nullable|integer|exists:gps_readings,reading_id',
            'entered_at' => 'sometimes|date',
            'exited_at' => 'nullable|date',
        ]);
        $visit->update($validated);
        return new AnimalGeoZoneResource($visit);
    }

    public function destroy($id)
    {
        $visit = AnimalGeoZone::find($id);
        if (!$visit) {
            return response()->json(['message' => 'AnimalGeoZone not found'], 404);
        }
        $visit->delete();
        return response()->json(['message' => 'AnimalGeoZone deleted']);
    }
}
