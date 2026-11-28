<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TourRoute extends Model
{
    protected $table = 'tour_routes';

    protected $primaryKey = 'route_id';

    protected $fillable = [
        'code', 'slug', 'name', 'entry_point', 'summary', 'description', 'difficulty', 'duration_hours', 'duration_days',
        'distance_km', 'max_group_size', 'min_age', 'meeting_point', 'meeting_lat', 'meeting_lon', 'start_time',
        'guide_fee_per_person', 'porter_fee_per_day', 'itinerary', 'includes', 'bring_list', 'rules', 'wildlife',
        'waypoints', 'emergency_contacts', 'post_details', 'zone_id', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'itinerary' => 'array',
            'includes' => 'array',
            'bring_list' => 'array',
            'rules' => 'array',
            'wildlife' => 'array',
            'waypoints' => 'array',
            'emergency_contacts' => 'array',
            'post_details' => 'array',
            'distance_km' => 'float',
            'meeting_lat' => 'float',
            'meeting_lon' => 'float',
            'guide_fee_per_person' => 'integer',
            'porter_fee_per_day' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function zone()
    {
        return $this->belongsTo(GeoZone::class, 'zone_id', 'zone_id');
    }

    public function shifts()
    {
        return $this->hasMany(GuideShift::class, 'route_id', 'route_id');
    }

    public function reviews()
    {
        return $this->hasMany(TourReview::class, 'route_id', 'route_id');
    }
}
