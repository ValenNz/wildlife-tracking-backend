<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GPSReadings extends Model
{
    use HasFactory;

    protected $table = 'gpsreadings';
    protected $primaryKey = 'reading_id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'device_id',
        'latitude',
        'longitude',
        'recorded_at',
        'altitude_meters',
        'accuracy_meters',
        'checksum',
    ];

    public $timestamps = false;

    protected $casts = [
        'recorded_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
        'altitude_meters' => 'float',
        'accuracy_meters' => 'float',
    ];

    // Relasi ke TrackingDevice
    public function trackingDevice()
    {
        return $this->belongsTo(TrackingDevice::class, 'device_id', 'device_id');
    }
}