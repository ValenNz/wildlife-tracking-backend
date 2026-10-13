<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrackingDevice extends Model
{
    protected $table = 'trackingdevices';
    protected $primaryKey = 'device_id';
    public $timestamps = false;

    public function animal()
    {
        return $this->belongsTo(Animal::class, 'animal_id', 'animal_id');
    }
}