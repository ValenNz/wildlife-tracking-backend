<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketPrice extends Model
{
    protected $table = 'ticket_prices';

    protected $primaryKey = 'price_id';

    protected $fillable = [
        'code', 'name', 'category', 'visitor_type', 'day_type', 'unit', 'amount', 'min_quantity', 'legal_basis', 'is_active',
    ];

    protected function casts(): array
    {
        return ['amount' => 'integer', 'min_quantity' => 'integer', 'is_active' => 'boolean'];
    }
}
