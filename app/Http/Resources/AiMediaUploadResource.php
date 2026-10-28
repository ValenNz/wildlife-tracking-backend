<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AiMediaUploadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'media_id' => $this->media_id,
            'uploaded_by' => $this->uploaded_by,
            'report_id' => $this->report_id,
            'media_type' => $this->media_type,
            'file_path' => $this->file_path,
            'uploaded_at' => $this->uploaded_at,
            'uploader' => new UserResource($this->whenLoaded('uploader')),
            'incident_report' => new IncidentReportResource($this->whenLoaded('incidentReport')),
            'ai_prediction' => new AiPredictionResource($this->whenLoaded('aiPrediction')),
        ];
    }
}
