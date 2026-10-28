<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AiPredictionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'prediction_id' => $this->prediction_id,
            'media_id' => $this->media_id,
            'predicted_species_id' => $this->predicted_species_id,
            'confidence' => $this->confidence,
            'model_version' => $this->model_version,
            'processing_status' => $this->processing_status,
            'processed_at' => $this->processed_at,
            'verification_status' => $this->verification_status,
            'verified_species_id' => $this->verified_species_id,
            'verified_by' => $this->verified_by,
            'verified_at' => $this->verified_at,
            'media_upload' => new AiMediaUploadResource($this->whenLoaded('mediaUpload')),
            'predicted_species' => new SpeciesResource($this->whenLoaded('predictedSpecies')),
            'verified_species' => new SpeciesResource($this->whenLoaded('verifiedSpecies')),
            'verifier' => new UserResource($this->whenLoaded('verifier')),
        ];
    }
}
