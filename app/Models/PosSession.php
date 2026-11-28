<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosSession extends Model
{
    protected $table = 'pos_sessions';

    protected $primaryKey = 'session_id';

    protected $fillable = ['cashier_id', 'counter_name', 'opened_at', 'opening_cash'];

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'opening_cash' => 'integer',
            'expected_cash' => 'integer',
            'counted_cash' => 'integer',
            'cash_difference' => 'integer',
        ];
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id', 'user_id');
    }

    public function sales()
    {
        return $this->hasMany(Booking::class, 'pos_session_id', 'session_id');
    }

    public function isOpen(): bool
    {
        return $this->closed_at === null;
    }
}
