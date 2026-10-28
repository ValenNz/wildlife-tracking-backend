<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AiPredictionResource;
use App\Models\AiPrediction;
use Illuminate\Http\Request;

class AiPredictionController extends Controller
{
    public function index()
    {
        $predictions = AiPrediction::with(['mediaUpload', 'predictedSpecies', 'verifiedSpecies', 'verifier'])->get();
        return AiPredictionResource::collection($predictions);
    }

    public function show($id)
    {
        $prediction = AiPrediction::with(['mediaUpload', 'predictedSpecies', 'verifiedSpecies', 'verifier'])->find($id);
        if (!$prediction) {
            return response()->json(['message' => 'AiPrediction not found'], 404);
        }
        return new AiPredictionResource($prediction);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'media_id' => 'required|integer|exists:ai_media_uploads,media_id',
            'predicted_species_id' => 'nullable|integer|exists:species,species_id',
            'confidence' => 'nullable|numeric',
            'model_version' => 'required|string|max:50',
            'processing_status' => 'required|in:queued,processing,completed,failed',
            'processed_at' => 'nullable|date',
            'verification_status' => 'required|in:pending,confirmed,rejected',
            'verified_species_id' => 'nullable|integer|exists:species,species_id',
            'verified_by' => 'nullable|integer|exists:users,user_id',
            'verified_at' => 'nullable|date',
        ]);
        $prediction = AiPrediction::create($validated);
        return new AiPredictionResource($prediction);
    }

    public function update(Request $request, $id)
    {
        $prediction = AiPrediction::find($id);
        if (!$prediction) {
            return response()->json(['message' => 'AiPrediction not found'], 404);
        }
        $validated = $request->validate([
            'media_id' => 'sometimes|integer|exists:ai_media_uploads,media_id',
            'predicted_species_id' => 'nullable|integer|exists:species,species_id',
            'confidence' => 'nullable|numeric',
            'model_version' => 'sometimes|string|max:50',
            'processing_status' => 'sometimes|in:queued,processing,completed,failed',
            'processed_at' => 'nullable|date',
            'verification_status' => 'sometimes|in:pending,confirmed,rejected',
            'verified_species_id' => 'nullable|integer|exists:species,species_id',
            'verified_by' => 'nullable|integer|exists:users,user_id',
            'verified_at' => 'nullable|date',
        ]);
        $prediction->update($validated);
        return new AiPredictionResource($prediction);
    }

    public function destroy($id)
    {
        $prediction = AiPrediction::find($id);
        if (!$prediction) {
            return response()->json(['message' => 'AiPrediction not found'], 404);
        }
        $prediction->delete();
        return response()->json(['message' => 'AiPrediction deleted']);
    }
}
