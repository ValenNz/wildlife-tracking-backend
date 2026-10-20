<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GPSReadings extends Model
{
    use HasFactory;

    protected $table = 'gpsreadings';

    protected $primaryKey = 'reading_id';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'device_id',
        'latitude',
        'longitude',
        'recorded_at',
        'altitude_meters',
        'accuracy_meters',
        'checksum',
    ];

    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
            'latitude' => 'decimal:6',
            'longitude' => 'decimal:6',
            'altitude_meters' => 'decimal:2',
            'accuracy_meters' => 'decimal:2',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(TrackingDevice::class, 'device_id', 'device_id');
    }

    public function zoneVisits(): HasMany
    {
        return $this->hasMany(AnimalGeoZone::class, 'entry_reading_id', 'reading_id');
    }

    public function weatherData(): HasMany
    {
        return $this->hasMany(WeatherData::class, 'reading_id', 'reading_id');
    }
}