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
        'temperature',
        'humidity',
        'rainfall',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'temperature' => 'float',
            'humidity' => 'float',
            'rainfall' => 'float',
            'recorded_at' => 'datetime',
        ];
    }

    public function reading(): BelongsTo
    {
        return $this->belongsTo(GPSReadings::class, 'reading_id', 'reading_id');
    }
}
