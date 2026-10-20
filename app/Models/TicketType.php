<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketType extends Model
{
    use HasFactory;

    protected $table = 'ticket_types';

    protected $primaryKey = 'ticket_type_id';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'type_name',
        'description',
        'price',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function bookingItems(): HasMany
    {
        return $this->hasMany(BookingItem::class, 'ticket_type_id', 'ticket_type_id');
    }
}