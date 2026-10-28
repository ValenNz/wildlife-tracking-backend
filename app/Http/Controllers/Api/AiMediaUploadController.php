<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AiMediaUploadResource;
use App\Models\AiMediaUpload;
use Illuminate\Http\Request;

class AiMediaUploadController extends Controller
{
    public function index()
    {
        $media = AiMediaUpload::with(['uploader', 'incidentReport'])->get();
        return AiMediaUploadResource::collection($media);
    }

    public function show($id)
    {
        $media = AiMediaUpload::with(['uploader', 'incidentReport', 'aiPrediction'])->find($id);
        if (!$media) {
            return response()->json(['message' => 'AiMediaUpload not found'], 404);
        }
        return new AiMediaUploadResource($media);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'uploaded_by' => 'nullable|integer|exists:users,user_id',
            'report_id' => 'nullable|integer|exists:incident_reports,report_id',
            'media_type' => 'required|in:image,video',
            'file_path' => 'required|string|max:500',
            'uploaded_at' => 'nullable|date',
        ]);
        $media = AiMediaUpload::create($validated);
        return new AiMediaUploadResource($media);
    }

    public function update(Request $request, $id)
    {
        $media = AiMediaUpload::find($id);
        if (!$media) {
            return response()->json(['message' => 'AiMediaUpload not found'], 404);
        }
        $validated = $request->validate([
            'uploaded_by' => 'nullable|integer|exists:users,user_id',
            'report_id' => 'nullable|integer|exists:incident_reports,report_id',
            'media_type' => 'sometimes|in:image,video',
            'file_path' => 'sometimes|string|max:500',
            'uploaded_at' => 'nullable|date',
        ]);
        $media->update($validated);
        return new AiMediaUploadResource($media);
    }

    public function destroy($id)
    {
        $media = AiMediaUpload::find($id);
        if (!$media) {
            return response()->json(['message' => 'AiMediaUpload not found'], 404);
        }
        $media->delete();
        return response()->json(['message' => 'AiMediaUpload deleted']);
    }
}
