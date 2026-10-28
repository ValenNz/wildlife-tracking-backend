<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GpsReadingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'reading_id' => $this->reading_id,
            'device_id' => $this->device_id,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'recorded_at' => $this->recorded_at,
            'altitude_meters' => $this->altitude_meters,
            'accuracy_meters' => $this->accuracy_meters,
            'checksum' => $this->checksum,
            'tracking_device' => new TrackingDeviceResource($this->whenLoaded('trackingDevice')),
            'weather_data' => new WeatherDataResource($this->whenLoaded('weatherData')),
        ];
    }
}
