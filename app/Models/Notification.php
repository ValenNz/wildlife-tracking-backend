<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    protected $table = 'notifications';
    protected $primaryKey = 'notif_id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false; // pakai created_at saja

    protected $fillable = [
        'user_id',
        'notification_type',
        'message',
        'animal_id',
        'zone_id',
        'device_id',
        'is_read',
    ];

    protected function casts(): array
    {
        return [
            'is_read'    => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    // === RELATIONS ===

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'animal_id', 'animal_id');
    }

    public function geoZone(): BelongsTo
    {
        return $this->belongsTo(GeoZone::class, 'zone_id', 'zone_id');
    }

    public function trackingDevice(): BelongsTo
    {
        return $this->belongsTo(TrackingDevice::class, 'device_id', 'device_id');
    }
}