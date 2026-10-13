<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AIMediaUpload extends Model
{
    use HasFactory;

    protected $table = 'aimediauploads';
    protected $primaryKey = 'media_id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false; // pakai uploaded_at

    protected $fillable = [
        'uploaded_by',
        'report_id',
        'media_type',
        'file_path',
    ];

    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
        ];
    }

    // === RELATIONS ===

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by', 'user_id');
    }

    public function incidentReport(): BelongsTo
    {
        return $this->belongsTo(IncidentReport::class, 'report_id', 'report_id');
    }

    public function predictions(): HasMany
    {
        return $this->hasMany(AIPrediction::class, 'media_id', 'media_id');
    }
}