<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PermissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'permission_id' => $this->permission_id,
            'permission_name' => $this->permission_name,
            'description' => $this->description,
            'created_at' => $this->created_at,
        ];
    }
}
