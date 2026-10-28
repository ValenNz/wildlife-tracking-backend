<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SpeciesResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'species_id' => $this->species_id,
            'common_name' => $this->common_name,
            'scientific_name' => $this->scientific_name,
            'conservation_status' => $this->conservation_status,
            'animals' => AnimalResource::collection($this->whenLoaded('animals')),
        ];
    }
}
