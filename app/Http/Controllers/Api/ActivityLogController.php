<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityLogResource;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index()
    {
        $logs = ActivityLog::with(['user'])->get();
        return ActivityLogResource::collection($logs);
    }

    public function show($id)
    {
        $log = ActivityLog::with(['user'])->find($id);
        if (!$log) {
            return response()->json(['message' => 'ActivityLog not found'], 404);
        }
        return new ActivityLogResource($log);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|integer|exists:users,user_id',
            'action' => 'required|string|max:100',
            'timestamp' => 'nullable|date',
            'details' => 'nullable|string',
        ]);
        $log = ActivityLog::create($validated);
        return new ActivityLogResource($log);
    }

    public function update(Request $request, $id)
    {
        $log = ActivityLog::find($id);
        if (!$log) {
            return response()->json(['message' => 'ActivityLog not found'], 404);
        }
        $validated = $request->validate([
            'user_id' => 'nullable|integer|exists:users,user_id',
            'action' => 'sometimes|string|max:100',
            'timestamp' => 'nullable|date',
            'details' => 'nullable|string',
        ]);
        $log->update($validated);
        return new ActivityLogResource($log);
    }

    public function destroy($id)
    {
        $log = ActivityLog::find($id);
        if (!$log) {
            return response()->json(['message' => 'ActivityLog not found'], 404);
        }
        $log->delete();
        return response()->json(['message' => 'ActivityLog deleted']);
    }
}
