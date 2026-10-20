<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Animal extends Model
{
    protected $primaryKey = 'animal_id';
<<<<<<< HEAD

    public $incrementing = true;

    protected $keyType = 'int';

    public const UPDATED_AT = null;
    public const CREATED_AT = null;

=======
    public $incrementing = false;
    protected $keyType = 'integer';
    public $timestamps = false;
    
>>>>>>> 3de367708937d5e810dcfc4b431e1ca54441daf1
    protected $fillable = [
        'animal_id',
        'species_id',
        'name',
        'birth_date',
        'gender',
        'is_active',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function species(): BelongsTo
    {
        return $this->belongsTo(Species::class, 'species_id', 'species_id');
    }

    public function trackingDevice(): HasMany
    {
        return $this->hasMany(TrackingDevice::class, 'animal_id', 'animal_id');
    }

    public function geoZoneVisits(): HasMany
    {
        return $this->hasMany(AnimalGeoZone::class, 'animal_id', 'animal_id');
    }
<<<<<<< HEAD

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'animal_id', 'animal_id');
    }

    public function incidentReports(): HasMany
    {
        return $this->hasMany(IncidentReport::class, 'animal_code', 'name');
    }
=======
>>>>>>> 3de367708937d5e810dcfc4b431e1ca54441daf1
}