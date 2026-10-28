<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GeoZoneResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'zone_id' => $this->zone_id,
            'zone_name' => $this->zone_name,
            'zone_type' => $this->zone_type,
            'center_lat' => $this->center_lat,
            'center_lon' => $this->center_lon,
            'radius_km' => $this->radius_km,
            'polygon_geojson' => $this->polygon_geojson,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
            'creator' => new UserResource($this->whenLoaded('creator')),
        ];
    }
}
