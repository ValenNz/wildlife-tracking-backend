<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeatherData extends Model
{
    use HasFactory;

    protected $table = 'weatherdata';

    protected $primaryKey = 'weather_id';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'reading_id',
        'latitude',
        'longitude',
        'observed_at',
        'temperature_c',
        'rainfall_mm',
        'humidity_percent',
        'pressure_hpa',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:6',
            'longitude' => 'decimal:6',
            'observed_at' => 'datetime',
            'temperature_c' => 'decimal:2',
            'rainfall_mm' => 'decimal:2',
            'humidity_percent' => 'decimal:2',
            'pressure_hpa' => 'decimal:2',
        ];
    }

    public function reading(): BelongsTo
    {
        return $this->belongsTo(GPSReadings::class, 'reading_id', 'reading_id');
    }
}