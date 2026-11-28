<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuideShift extends Model
{
    public const TYPES = ['pagi', 'siang', 'malam'];

    protected $table = 'guide_shifts';

    protected $primaryKey = 'shift_id';

    protected $fillable = ['guide_id', 'route_id', 'shift_date', 'shift_type', 'start_time', 'end_time', 'capacity', 'status'];

    protected function casts(): array
    {
        return ['shift_date' => 'date:Y-m-d', 'capacity' => 'integer'];
    }

    public function guide()
    {
        return $this->belongsTo(TourGuide::class, 'guide_id', 'guide_id');
    }

    public function route()
    {
        return $this->belongsTo(TourRoute::class, 'route_id', 'route_id');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'shift_id', 'shift_id');
    }
}
