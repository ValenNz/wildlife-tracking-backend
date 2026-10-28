<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GeoZoneResource;
use App\Models\GeoZone;
use Illuminate\Http\Request;

class GeoZoneController extends Controller
{
    public function index()
    {
        $zones = GeoZone::with(['creator'])->get();
        return GeoZoneResource::collection($zones);
    }

    public function show($id)
    {
        $zone = GeoZone::with(['creator'])->find($id);
        if (!$zone) {
            return response()->json(['message' => 'GeoZone not found'], 404);
        }
        return new GeoZoneResource($zone);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'zone_name' => 'required|string|max:150',
            'zone_type' => 'required|string|max:50',
            'center_lat' => 'nullable|numeric',
            'center_lon' => 'nullable|numeric',
            'radius_km' => 'nullable|numeric',
            'polygon_geojson' => 'nullable',
            'created_by' => 'nullable|integer|exists:users,user_id',
        ]);
        $zone = GeoZone::create($validated);
        return new GeoZoneResource($zone);
    }

    public function update(Request $request, $id)
    {
        $zone = GeoZone::find($id);
        if (!$zone) {
            return response()->json(['message' => 'GeoZone not found'], 404);
        }
        $validated = $request->validate([
            'zone_name' => 'sometimes|string|max:150',
            'zone_type' => 'sometimes|string|max:50',
            'center_lat' => 'nullable|numeric',
            'center_lon' => 'nullable|numeric',
            'radius_km' => 'nullable|numeric',
            'polygon_geojson' => 'nullable',
            'created_by' => 'nullable|integer|exists:users,user_id',
        ]);
        $zone->update($validated);
        return new GeoZoneResource($zone);
    }

    public function destroy($id)
    {
        $zone = GeoZone::find($id);
        if (!$zone) {
            return response()->json(['message' => 'GeoZone not found'], 404);
        }
        $zone->delete();
        return response()->json(['message' => 'GeoZone deleted']);
    }
}
