<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $table = 'activitylog'; // Nama tabel di DB
    protected $primaryKey = 'log_id'; // Primary key custom
    
    // Matikan timestamps default Laravel (created_at/updated_at)
    // karena tabel Anda hanya punya kolom 'timestamp'
    public $timestamps = false; 

    protected $fillable = [
        'user_id',
        'action',
        'timestamp',
        'details'
    ];
}