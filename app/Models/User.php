<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users';
    protected $primaryKey = 'user_id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false; // hanya ada created_at

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    // === RELATIONS ===

    public function incidentReports(): HasMany
    {
        return $this->hasMany(IncidentReport::class, 'user_id', 'user_id');
    }

    public function geoZones(): HasMany
    {
        return $this->hasMany(GeoZone::class, 'created_by', 'user_id');
    }

    public function aiMediaUploads(): HasMany
    {
        return $this->hasMany(AIMediaUpload::class, 'uploaded_by', 'user_id');
    }

    public function aiPredictionsVerified(): HasMany
    {
        return $this->hasMany(AIPrediction::class, 'verified_by', 'user_id');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class, 'user_id', 'user_id');
    }

    public function aggregateReports(): HasMany
    {
        return $this->hasMany(AggregateReport::class, 'generated_by', 'user_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'user_id', 'user_id');
    }
}