<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    // Sesuaikan dengan tabel di database
    protected $table = 'users';
    protected $primaryKey = 'user_id';
    
    // Di SQL Anda hanya ada created_at, tidak ada updated_at
    public $timestamps = false; 
    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

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
}