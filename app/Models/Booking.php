<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    use HasFactory;

    protected $table = 'bookings';

    protected $primaryKey = 'booking_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'booking_code',
        'visitor_id',
        'visit_date',
        'total_amount',
        'booking_status',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'visit_date' => 'date',
            'total_amount' => 'decimal:2',
            'expires_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(Visitor::class, 'visitor_id', 'visitor_id');
    }

    public function bookingItems(): HasMany
    {
        return $this->hasMany(BookingItem::class, 'booking_id', 'booking_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'booking_id', 'booking_id');
    }
}