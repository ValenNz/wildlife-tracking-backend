<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrackingDevice extends Model
{
    use HasFactory;

    protected $table = 'trackingdevices';

    protected $primaryKey = 'device_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'animal_id',
        'model',
        'installation_date',
        'battery_life_months',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'installation_date' => 'date',
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
