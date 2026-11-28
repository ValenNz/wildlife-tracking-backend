<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TourReview extends Model
{
    protected $table = 'tour_reviews';

    protected $primaryKey = 'review_id';

    protected $fillable = ['booking_id', 'user_id', 'route_id', 'guide_id', 'route_rating', 'guide_rating', 'comment', 'is_visible'];

    protected $hidden = ['user_id'];

    protected function casts(): array
    {
        return ['is_visible' => 'boolean', 'route_rating' => 'integer', 'guide_rating' => 'integer'];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function route()
    {
        return $this->belongsTo(TourRoute::class, 'route_id', 'route_id');
    }

    public function guide()
    {
        return $this->belongsTo(TourGuide::class, 'guide_id', 'guide_id');
    }
}
