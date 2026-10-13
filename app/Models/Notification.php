<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'notifications';
    protected $primaryKey = 'notif_id';
    public $timestamps = false;

    const CREATED_AT = 'created_at';
}