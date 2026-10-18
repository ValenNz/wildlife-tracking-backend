<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GeoZone extends Model
{
    use HasFactory;

    protected $table = 'geozones';

    protected $primaryKey = 'zone_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'zone_name',
        'zone_type',
        'polygon_data',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'polygon_data' => 'array',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function animalVisits(): HasMany
    {
        return $this->hasMany(AnimalGeoZone::class, 'zone_id', 'zone_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'zone_id', 'zone_id');
    }
}
