<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WeatherDataResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'weather_id' => $this->weather_id,
            'reading_id' => $this->reading_id,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'observed_at' => $this->observed_at,
            'temperature_c' => $this->temperature_c,
            'rainfall_mm' => $this->rainfall_mm,
            'humidity_percent' => $this->humidity_percent,
            'pressure_hpa' => $this->pressure_hpa,
            'source' => $this->source,
            'gps_reading' => new GpsReadingResource($this->whenLoaded('gpsReading')),
        ];
    }
}
