<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    public const HOLDING_STATUSES = ['pending_payment', 'awaiting_verification', 'paid', 'checked_in', 'completed'];

    protected $table = 'bookings';

    protected $primaryKey = 'booking_id';

    protected $fillable = [
        'booking_code', 'user_id', 'channel', 'pos_session_id', 'sold_by', 'route_id', 'shift_id', 'visit_date', 'day_type', 'wni_count', 'wna_count',
        'student_count', 'drone_count', 'porter_count', 'participant_count', 'price_lines', 'total_amount', 'status', 'expires_at',
        'contact_name', 'contact_phone', 'emergency_contact', 'notes',
    ];

    protected $hidden = ['ticket_token_hash'];

    protected function casts(): array
    {
        return [
            'visit_date' => 'date:Y-m-d',
            'price_lines' => 'array',
            'porter_count' => 'integer',
            'total_amount' => 'integer',
            'expires_at' => 'datetime',
            'checked_in_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function scopeHoldingCapacity(Builder $query): Builder
    {
        return $query->whereIn('status', self::HOLDING_STATUSES)
            ->where(fn ($q) => $q->where('status', '!=', 'pending_payment')->orWhere('expires_at', '>', now()));
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'sold_by', 'user_id');
    }

    public function posSession()
    {
        return $this->belongsTo(PosSession::class, 'pos_session_id', 'session_id');
    }

    public function route()
    {
        return $this->belongsTo(TourRoute::class, 'route_id', 'route_id');
    }

    public function shift()
    {
        return $this->belongsTo(GuideShift::class, 'shift_id', 'shift_id');
    }

    public function participants()
    {
        return $this->hasMany(BookingParticipant::class, 'booking_id', 'booking_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'booking_id', 'booking_id');
    }

    public function review()
    {
        return $this->hasOne(TourReview::class, 'booking_id', 'booking_id');
    }
}
