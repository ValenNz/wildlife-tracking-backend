<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncidentReport extends Model
{
    protected $table = 'incident_reports';
    protected $primaryKey = 'report_id';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'incident_type',
        'animal_code',
        'location',
        'description',
        'timestamp',
        'photo_url',
        'sync_status',
    ];

    protected function casts(): array
    {
        return [
            'timestamp' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function aiMediaUploads()
    {
        return $this->hasMany(AiMediaUpload::class, 'report_id', 'report_id');
    }
}
