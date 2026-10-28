<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeoZone extends Model
{
    protected $table = 'geo_zones';
    protected $primaryKey = 'zone_id';
    public $timestamps = false;

    protected $fillable = [
        'zone_name',
        'zone_type',
        'center_lat',
        'center_lon',
        'radius_km',
        'polygon_geojson',
        'created_by',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'center_lat' => 'decimal:6',
            'center_lon' => 'decimal:6',
            'radius_km' => 'decimal:3',
            'polygon_geojson' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function animalGeoZones()
    {
        return $this->hasMany(AnimalGeoZone::class, 'zone_id', 'zone_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'zone_id', 'zone_id');
    }
}
