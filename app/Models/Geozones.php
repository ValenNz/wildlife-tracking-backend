<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Geozones extends Model
{
    protected $table = 'geozones';
    protected $primaryKey = 'zone_id';
    public $timestamps = false;
}