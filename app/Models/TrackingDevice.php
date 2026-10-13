<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'is_active',
        'device_condition',
        'battery_level',
        'signal_strength',
        'last_recorded_at',
    ];

    public $timestamps = false;

    protected $casts = [
        'installation_date' => 'date',
        'last_recorded_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    // Relasi ke Animal
    public function animal()
    {
        return $this->belongsTo(Animal::class, 'animal_id', 'animal_id');
    }

    // Relasi ke GPSReadings
    public function gpsReadings()
    {
        return $this->hasMany(GPSReadings::class, 'device_id', 'device_id');
    }
}