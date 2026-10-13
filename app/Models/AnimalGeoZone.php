<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnimalGeoZone extends Model
{
    use HasFactory;

    protected $table = 'animalgeozones';
    protected $primaryKey = 'visit_id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'animal_id',
        'zone_id',
        'entry_reading_id',
        'entered_at',
        'exited_at',
    ];

    protected function casts(): array
    {
        return [
            'entered_at' => 'datetime',
            'exited_at'  => 'datetime',
        ];
    }

    // === RELATIONS ===

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'animal_id', 'animal_id');
    }

    public function geoZone(): BelongsTo
    {
        return $this->belongsTo(GeoZone::class, 'zone_id', 'zone_id');
    }

    public function entryReading(): BelongsTo
    {
        return $this->belongsTo(GPSReading::class, 'entry_reading_id', 'reading_id');
    }
}