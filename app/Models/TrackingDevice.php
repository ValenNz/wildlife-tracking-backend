<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrackingDevice extends Model
{
    use HasFactory;

    protected $table = 'tracking_devices';

    protected $primaryKey = 'device_id';

    public $incrementing = true;

    protected $keyType = 'int';

    public const UPDATED_AT = null;
    public const CREATED_AT = null;

    protected $fillable = [
        'animal_id',
        'model',
        'installation_date',
        'battery_life_months',
        'is_active',
        'device_condition',
        'battery_level',
        'signal_strength',
        'last_recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'installation_date' => 'date',
            'is_active' => 'boolean',
            'battery_level' => 'integer',
            'signal_strength' => 'integer',
            'last_recorded_at' => 'datetime',
        ];
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'animal_id', 'animal_id');
    }

    public function gpsReadings(): HasMany
    {
        return $this->hasMany(GPSReadings::class, 'device_id', 'device_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'device_id', 'device_id');
    }
}