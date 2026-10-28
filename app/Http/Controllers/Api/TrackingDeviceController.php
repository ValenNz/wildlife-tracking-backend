<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TrackingDeviceResource;
use App\Models\TrackingDevice;
use Illuminate\Http\Request;

class TrackingDeviceController extends Controller
{
    public function index()
    {
        $devices = TrackingDevice::with(['animal'])->get();
        return TrackingDeviceResource::collection($devices);
    }

    public function show($id)
    {
        $device = TrackingDevice::with(['animal', 'gpsReadings'])->find($id);
        if (!$device) {
            return response()->json(['message' => 'Device not found'], 404);
        }
        return new TrackingDeviceResource($device);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'animal_id' => 'nullable|integer|exists:animals,animal_id',
            'model' => 'required|string|max:100',
            'installation_date' => 'nullable|date',
            'battery_life_months' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'device_condition' => 'required|in:normal,damaged,lost',
            'battery_level' => 'nullable|integer|min:0|max:100',
            'signal_strength' => 'nullable|integer',
            'last_recorded_at' => 'nullable|date',
        ]);
        $device = TrackingDevice::create($validated);
        return new TrackingDeviceResource($device);
    }

    public function update(Request $request, $id)
    {
        $device = TrackingDevice::find($id);
        if (!$device) {
            return response()->json(['message' => 'Device not found'], 404);
        }
        $validated = $request->validate([
            'animal_id' => 'nullable|integer|exists:animals,animal_id',
            'model' => 'sometimes|string|max:100',
            'installation_date' => 'nullable|date',
            'battery_life_months' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'device_condition' => 'sometimes|in:normal,damaged,lost',
            'battery_level' => 'nullable|integer|min:0|max:100',
            'signal_strength' => 'nullable|integer',
            'last_recorded_at' => 'nullable|date',
        ]);
        $device->update($validated);
        return new TrackingDeviceResource($device);
    }

    public function destroy($id)
    {
        $device = TrackingDevice::find($id);
        if (!$device) {
            return response()->json(['message' => 'Device not found'], 404);
        }
        $device->delete();
        return response()->json(['message' => 'Device deleted']);
    }
}
