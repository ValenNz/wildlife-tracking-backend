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
<<<<<<< HEAD
=======
        'user_id',
>>>>>>> 3de367708937d5e810dcfc4b431e1ca54441daf1
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
<<<<<<< HEAD

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'visitor_id', 'visitor_id');
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }
=======
>>>>>>> 3de367708937d5e810dcfc4b431e1ca54441daf1
}