<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiMediaUpload extends Model
{
    protected $table = 'ai_media_uploads';
    protected $primaryKey = 'media_id';
    public $timestamps = false;

    protected $fillable = [
        'uploaded_by',
        'report_id',
        'media_type',
        'file_path',
        'uploaded_at',
    ];

    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
        ];
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by', 'user_id');
    }

    public function incidentReport()
    {
        return $this->belongsTo(IncidentReport::class, 'report_id', 'report_id');
    }

    public function aiPrediction()
    {
        return $this->hasOne(AiPrediction::class, 'media_id', 'media_id');
    }
}
