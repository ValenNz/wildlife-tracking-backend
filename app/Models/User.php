<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'users';
    protected $primaryKey = 'user_id';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'created_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'created_at' => 'datetime',
        ];
    }

    public function incidentReports()
    {
        return $this->hasMany(IncidentReport::class, 'user_id', 'user_id');
    }

    public function aiMediaUploads()
    {
        return $this->hasMany(AiMediaUpload::class, 'uploaded_by', 'user_id');
    }

    public function verifiedAiPredictions()
    {
        return $this->hasMany(AiPrediction::class, 'verified_by', 'user_id');
    }

    public function geoZones()
    {
        return $this->hasMany(GeoZone::class, 'created_by', 'user_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'user_id', 'user_id');
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class, 'user_id', 'user_id');
    }

    public function aggregateReports()
    {
        return $this->hasMany(AggregateReport::class, 'generated_by', 'user_id');
    }
}
