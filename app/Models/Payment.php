<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $table = 'payments';

    protected $primaryKey = 'payment_id';

    protected $fillable = ['booking_id', 'reference', 'method', 'amount', 'received_amount', 'change_amount', 'external_reference', 'status'];

    protected $hidden = ['proof_path'];

    protected $appends = ['has_proof'];

    protected function casts(): array
    {
        return ['amount' => 'integer', 'received_amount' => 'integer', 'change_amount' => 'integer', 'paid_at' => 'datetime'];
    }

    public function getHasProofAttribute(): bool
    {
        return $this->proof_path !== null;
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id', 'booking_id');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by', 'user_id');
    }
}
