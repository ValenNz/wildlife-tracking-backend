<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use HasFactory;

    protected $table = 'activity_log';

    protected $primaryKey = 'log_id';

    // Karena kita menggunakan $table->id(), ini harus true
    public $incrementing = true;
    protected $keyType = 'int';

    public $timestamps = false; // Kita hanya pakai 'timestamp' manual

    protected $fillable = [
        'user_id',
        'action',
        'details',
    ];

    protected function casts(): array
    {
        return [
            'timestamp' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}