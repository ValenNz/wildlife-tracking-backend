<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = Notification::with(['user', 'animal', 'geoZone', 'trackingDevice'])->get();
        return NotificationResource::collection($notifications);
    }

    public function show($id)
    {
        $notification = Notification::with(['user', 'animal', 'geoZone', 'trackingDevice'])->find($id);
        if (!$notification) {
            return response()->json(['message' => 'Notification not found'], 404);
        }
        return new NotificationResource($notification);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,user_id',
            'notification_type' => 'required|in:risk_zone,device_alert,system',
            'message' => 'required|string|max:500',
            'animal_id' => 'nullable|integer|exists:animals,animal_id',
            'zone_id' => 'nullable|integer|exists:geo_zones,zone_id',
            'device_id' => 'nullable|integer|exists:tracking_devices,device_id',
            'is_read' => 'nullable|boolean',
        ]);
        $notification = Notification::create($validated);
        return new NotificationResource($notification);
    }

    public function update(Request $request, $id)
    {
        $notification = Notification::find($id);
        if (!$notification) {
            return response()->json(['message' => 'Notification not found'], 404);
        }
        $validated = $request->validate([
            'user_id' => 'sometimes|integer|exists:users,user_id',
            'notification_type' => 'sometimes|in:risk_zone,device_alert,system',
            'message' => 'sometimes|string|max:500',
            'animal_id' => 'nullable|integer|exists:animals,animal_id',
            'zone_id' => 'nullable|integer|exists:geo_zones,zone_id',
            'device_id' => 'nullable|integer|exists:tracking_devices,device_id',
            'is_read' => 'nullable|boolean',
        ]);
        $notification->update($validated);
        return new NotificationResource($notification);
    }

    public function destroy($id)
    {
        $notification = Notification::find($id);
        if (!$notification) {
            return response()->json(['message' => 'Notification not found'], 404);
        }
        $notification->delete();
        return response()->json(['message' => 'Notification deleted']);
    }
}
