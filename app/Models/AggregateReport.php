<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AggregateReport extends Model
{
    use HasFactory;

    protected $table = 'aggregatereports';

    protected $primaryKey = 'report_id';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'generated_by',
        'report_type',
        'period_start',
        'period_end',
        'data_summary',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'data_summary' => 'array',
            'generated_at' => 'datetime',
        ];
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by', 'user_id');
    }
}
