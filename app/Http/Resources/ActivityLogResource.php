<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'log_id' => $this->log_id,
            'user_id' => $this->user_id,
            'action' => $this->action,
            'timestamp' => $this->timestamp,
            'details' => $this->details,
            'user' => new UserResource($this->whenLoaded('user')),
        ];
    }
}
