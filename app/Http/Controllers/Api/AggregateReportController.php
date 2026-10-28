<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AggregateReportResource;
use App\Models\AggregateReport;
use Illuminate\Http\Request;

class AggregateReportController extends Controller
{
    public function index()
    {
        $reports = AggregateReport::with(['generator'])->get();
        return AggregateReportResource::collection($reports);
    }

    public function show($id)
    {
        $report = AggregateReport::with(['generator'])->find($id);
        if (!$report) {
            return response()->json(['message' => 'AggregateReport not found'], 404);
        }
        return new AggregateReportResource($report);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'generated_by' => 'nullable|integer|exists:users,user_id',
            'report_type' => 'required|in:daily,weekly,monthly',
            'period_start' => 'required|date',
            'period_end' => 'required|date',
            'generated_at' => 'nullable|date',
            'file_path' => 'nullable|string|max:500',
        ]);
        $report = AggregateReport::create($validated);
        return new AggregateReportResource($report);
    }

    public function update(Request $request, $id)
    {
        $report = AggregateReport::find($id);
        if (!$report) {
            return response()->json(['message' => 'AggregateReport not found'], 404);
        }
        $validated = $request->validate([
            'generated_by' => 'nullable|integer|exists:users,user_id',
            'report_type' => 'sometimes|in:daily,weekly,monthly',
            'period_start' => 'sometimes|date',
            'period_end' => 'sometimes|date',
            'generated_at' => 'nullable|date',
            'file_path' => 'nullable|string|max:500',
        ]);
        $report->update($validated);
        return new AggregateReportResource($report);
    }

    public function destroy($id)
    {
        $report = AggregateReport::find($id);
        if (!$report) {
            return response()->json(['message' => 'AggregateReport not found'], 404);
        }
        $report->delete();
        return response()->json(['message' => 'AggregateReport deleted']);
    }
}
