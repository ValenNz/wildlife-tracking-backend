<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PublicHoliday extends Model
{
    protected $table = 'public_holidays';

    protected $primaryKey = 'holiday_id';

    protected $fillable = ['holiday_date', 'name'];

    protected function casts(): array
    {
        return ['holiday_date' => 'date:Y-m-d'];
    }
}
