<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Animal extends Model
{
    use HasFactory;

    protected $table = 'animals';

    protected $primaryKey = 'animal_id';

    public $incrementing = true;

    protected $keyType = 'int';

    public const UPDATED_AT = null;
    public const CREATED_AT = null;

    protected $fillable = [
        'species_id',
        'name',
        'birth_date',
        'gender',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function species(): BelongsTo
    {
        return $this->belongsTo(Species::class, 'species_id', 'species_id');
    }

    public function trackingDevice(): HasOne
    {
        return $this->hasOne(TrackingDevice::class, 'animal_id', 'animal_id');
    }

    public function geoZoneVisits(): HasMany
    {
        return $this->hasMany(AnimalGeoZone::class, 'animal_id', 'animal_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'animal_id', 'animal_id');
    }

    public function incidentReports(): HasMany
    {
        return $this->hasMany(IncidentReport::class, 'animal_code', 'name');
    }
}