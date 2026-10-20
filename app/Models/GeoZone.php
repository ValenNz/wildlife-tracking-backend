<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GeoZone extends Model
{
    use HasFactory;

    protected $table = 'geozones';

    protected $primaryKey = 'zone_id';

    public $incrementing = true;

    protected $keyType = 'int';

    public const UPDATED_AT = null;

    protected $fillable = [
        'zone_name',
        'zone_type',
        'center_lat',
        'center_lon',
        'radius_km',
        'polygon_geojson',
        'created_by',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function animalVisits(): HasMany
    {
        return $this->hasMany(AnimalGeoZone::class, 'zone_id', 'zone_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'zone_id', 'zone_id');
    }
}