<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\IncidentReportResource;
use App\Models\IncidentReport;
use Illuminate\Http\Request;

class IncidentReportController extends Controller
{
    public function index()
    {
        $reports = IncidentReport::with(['user'])->get();
        return IncidentReportResource::collection($reports);
    }

    public function show($id)
    {
        $report = IncidentReport::with(['user', 'aiMediaUploads'])->find($id);
        if (!$report) {
            return response()->json(['message' => 'IncidentReport not found'], 404);
        }
        return new IncidentReportResource($report);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|integer|exists:users,user_id',
            'incident_type' => 'required|string|max:50',
            'animal_code' => 'nullable|string|max:50',
            'location' => 'required|string|max:255',
            'description' => 'required|string',
            'timestamp' => 'nullable|date',
            'photo_url' => 'nullable|string|max:500',
            'sync_status' => 'required|in:pending_sync,synced,failed',
        ]);
        $report = IncidentReport::create($validated);
        return new IncidentReportResource($report);
    }

    public function update(Request $request, $id)
    {
        $report = IncidentReport::find($id);
        if (!$report) {
            return response()->json(['message' => 'IncidentReport not found'], 404);
        }
        $validated = $request->validate([
            'user_id' => 'nullable|integer|exists:users,user_id',
            'incident_type' => 'sometimes|string|max:50',
            'animal_code' => 'nullable|string|max:50',
            'location' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'timestamp' => 'nullable|date',
            'photo_url' => 'nullable|string|max:500',
            'sync_status' => 'sometimes|in:pending_sync,synced,failed',
        ]);
        $report->update($validated);
        return new IncidentReportResource($report);
    }

    public function destroy($id)
    {
        $report = IncidentReport::find($id);
        if (!$report) {
            return response()->json(['message' => 'IncidentReport not found'], 404);
        }
        $report->delete();
        return response()->json(['message' => 'IncidentReport deleted']);
    }
}
