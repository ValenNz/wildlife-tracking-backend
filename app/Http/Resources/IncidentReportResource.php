<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IncidentReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'report_id' => $this->report_id,
            'user_id' => $this->user_id,
            'incident_type' => $this->incident_type,
            'animal_code' => $this->animal_code,
            'location' => $this->location,
            'description' => $this->description,
            'timestamp' => $this->timestamp,
            'photo_url' => $this->photo_url,
            'sync_status' => $this->sync_status,
            'user' => new UserResource($this->whenLoaded('user')),
            'ai_media_uploads' => AiMediaUploadResource::collection($this->whenLoaded('aiMediaUploads')),
        ];
    }
}
