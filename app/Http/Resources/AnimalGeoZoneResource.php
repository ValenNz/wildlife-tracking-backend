<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnimalGeoZoneResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'visit_id' => $this->visit_id,
            'animal_id' => $this->animal_id,
            'zone_id' => $this->zone_id,
            'entry_reading_id' => $this->entry_reading_id,
            'entered_at' => $this->entered_at,
            'exited_at' => $this->exited_at,
            'animal' => new AnimalResource($this->whenLoaded('animal')),
            'geo_zone' => new GeoZoneResource($this->whenLoaded('geoZone')),
            'entry_reading' => new GpsReadingResource($this->whenLoaded('entryReading')),
        ];
    }
}
