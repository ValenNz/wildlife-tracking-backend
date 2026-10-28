<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'notif_id' => $this->notif_id,
            'user_id' => $this->user_id,
            'notification_type' => $this->notification_type,
            'message' => $this->message,
            'animal_id' => $this->animal_id,
            'zone_id' => $this->zone_id,
            'device_id' => $this->device_id,
            'is_read' => $this->is_read,
            'created_at' => $this->created_at,
            'user' => new UserResource($this->whenLoaded('user')),
            'animal' => new AnimalResource($this->whenLoaded('animal')),
            'geo_zone' => new GeoZoneResource($this->whenLoaded('geoZone')),
            'tracking_device' => new TrackingDeviceResource($this->whenLoaded('trackingDevice')),
        ];
    }
}
