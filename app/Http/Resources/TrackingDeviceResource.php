<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrackingDeviceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'device_id' => $this->device_id,
            'animal_id' => $this->animal_id,
            'model' => $this->model,
            'installation_date' => $this->installation_date,
            'battery_life_months' => $this->battery_life_months,
            'is_active' => $this->is_active,
            'device_condition' => $this->device_condition,
            'battery_level' => $this->battery_level,
            'signal_strength' => $this->signal_strength,
            'last_recorded_at' => $this->last_recorded_at,
            'animal' => new AnimalResource($this->whenLoaded('animal')),
            'gps_readings' => GpsReadingResource::collection($this->whenLoaded('gpsReadings')),
        ];
    }
}
