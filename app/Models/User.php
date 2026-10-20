<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Model
{
    protected $primaryKey = 'user_id';
    public $incrementing = false;
    protected $keyType = 'integer';
    public $timestamps = false;
    
    protected $fillable = [
        'user_id',
        'name',
        'email',
        'password',
        'role',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function incidentReports(): HasMany
    {
        return $this->hasMany(IncidentReport::class, 'user_id', 'user_id');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class, 'user_id', 'user_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'user_id', 'user_id');
    }
}