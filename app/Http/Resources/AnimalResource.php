<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnimalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'animal_id' => $this->animal_id,
            'species_id' => $this->species_id,
            'name' => $this->name,
            'birth_date' => $this->birth_date,
            'gender' => $this->gender,
            'is_active' => $this->is_active,
            'species' => new SpeciesResource($this->whenLoaded('species')),
            'tracking_device' => new TrackingDeviceResource($this->whenLoaded('trackingDevice')),
            'animal_geo_zones' => AnimalGeoZoneResource::collection($this->whenLoaded('animalGeoZones')),
        ];
    }
}
