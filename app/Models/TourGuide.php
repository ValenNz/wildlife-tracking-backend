<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TourGuide extends Model
{
    protected $table = 'tour_guides';

    protected $primaryKey = 'guide_id';

    protected $fillable = [
        'user_id', 'display_name', 'photo_path', 'bio', 'languages', 'specialties', 'favorite_animal',
        'favorite_animal_reason', 'years_experience', 'certification', 'fun_fact', 'is_active',
    ];

    protected $hidden = ['user_id', 'photo_path'];

    protected $appends = ['has_photo'];

    protected function casts(): array
    {
        return ['languages' => 'array', 'specialties' => 'array', 'is_active' => 'boolean'];
    }

    public function getHasPhotoAttribute(): bool
    {
        return $this->photo_path !== null;
    }

    public function shifts()
    {
        return $this->hasMany(GuideShift::class, 'guide_id', 'guide_id');
    }

    public function reviews()
    {
        return $this->hasMany(TourReview::class, 'guide_id', 'guide_id');
    }
}
