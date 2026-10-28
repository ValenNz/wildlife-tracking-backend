<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AggregateReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'report_id' => $this->report_id,
            'generated_by' => $this->generated_by,
            'report_type' => $this->report_type,
            'period_start' => $this->period_start,
            'period_end' => $this->period_end,
            'generated_at' => $this->generated_at,
            'file_path' => $this->file_path,
            'generator' => new UserResource($this->whenLoaded('generator')),
        ];
    }
}
